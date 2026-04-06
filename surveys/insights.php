<?php
/**
 * Darn Fine Insights — extensible insight generator framework.
 *
 * To add a new insight type:
 *   1. Define a function: function insight_my_type(array $questions, PDO $db, int $n): array
 *      - $questions  all questions for the survey, with pre-fetched counts/text_answers
 *      - $db         PDO connection for any additional queries
 *      - $n          total submission count
 *      - return      zero or more ['text' => string, 'score' => float] arrays
 *   2. Register it below: $generators[] = 'insight_my_type';
 */

$generators = [];

// ── Generator: Cross-question co-occurrence ───────────────────────────────
//
// Surfaces pairs of choices across two questions where ≥80% of respondents
// who picked option A in Question X also picked option B in Question Y,
// and that rate is at least 20 percentage points above the baseline for B.

function insight_cross_question(array $questions, PDO $db, int $submission_count): array
{
    $choice_types = ['radio', 'checkbox', 'select'];

    $choice_qs = [];
    foreach ($questions as $idx => $q) {
        if (in_array($q['type'], $choice_types, true)) {
            $choice_qs[] = ['q' => $q, 'num' => $idx + 1];
        }
    }

    if (count($choice_qs) < 2) return [];

    $insights = [];

    for ($i = 0; $i < count($choice_qs) - 1; $i++) {
        for ($j = $i + 1; $j < count($choice_qs); $j++) {
            $qa     = $choice_qs[$i]['q'];
            $qa_num = $choice_qs[$i]['num'];
            $qb     = $choice_qs[$j]['q'];
            $qb_num = $choice_qs[$j]['num'];

            $stmt = $db->prepare(
                'SELECT a1.value AS va, a2.value AS vb
                 FROM answers a1
                 JOIN answers a2 ON a2.submission_id = a1.submission_id
                 WHERE a1.question_id = ? AND a2.question_id = ?'
            );
            $stmt->execute([$qa['id'], $qb['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) < 5) continue;

            // Count per-submission: how often each (va, vb) co-occurs.
            // For checkbox questions, one submission can contribute multiple values,
            // so we count each (submission, va) and (submission, vb) pair once.
            $n_submissions = count($rows);
            $count_a       = [];  // submissions where va appears in Q_A
            $count_b       = [];  // submissions where vb appears in Q_B
            $count_ab      = [];  // submissions where both va in Q_A and vb in Q_B

            foreach ($rows as $row) {
                $vals_a = $qa['type'] === 'checkbox'
                    ? array_unique(json_decode($row['va'], true) ?? [])
                    : [$row['va']];
                $vals_b = $qb['type'] === 'checkbox'
                    ? array_unique(json_decode($row['vb'], true) ?? [])
                    : [$row['vb']];

                foreach ($vals_a as $va) {
                    $count_a[$va] = ($count_a[$va] ?? 0) + 1;
                    foreach ($vals_b as $vb) {
                        $count_ab[$va][$vb] = ($count_ab[$va][$vb] ?? 0) + 1;
                    }
                }
                foreach ($vals_b as $vb) {
                    $count_b[$vb] = ($count_b[$vb] ?? 0) + 1;
                }
            }

            foreach ($count_a as $va => $n_a) {
                if ($n_a < 5) continue;

                foreach (($count_ab[$va] ?? []) as $vb => $n_ab) {
                    $conditional = $n_ab / $n_a;
                    $baseline    = ($count_b[$vb] ?? 0) / $n_submissions;

                    // Surface only strong, non-obvious alignments
                    if ($conditional >= 0.80 && $conditional > $baseline + 0.20) {
                        $pct     = round($conditional * 100);
                        $qa_lbl  = $qa['label'];
                        $qb_lbl  = $qb['label'];
                        $insights[] = [
                            'text'  => "{$pct}% of respondents who chose \"{$va}\" for \"{$qa_lbl}\" also chose \"{$vb}\" for \"{$qb_lbl}\".",
                            'score' => $conditional - $baseline,
                        ];
                    }
                }
            }
        }
    }

    return $insights;
}

$generators[] = 'insight_cross_question';

// ── Generator: Common terms in text answers ───────────────────────────────
//
// For each text question, finds the most-mentioned significant word.
// Surfaces it if it appears in ≥40% of responses.

function insight_text_terms(array $questions, PDO $db, int $submission_count): array
{
    $stop_words = array_flip([
        'the','a','an','and','or','but','in','on','at','to','for','of','with','by',
        'is','it','its','be','are','was','were','have','has','do','does','did',
        'will','would','could','should','i','you','we','they','he','she','my',
        'your','our','their','this','that','these','those','not','no','so','as',
        'if','from','what','which','who','how','when','where','why','all','also',
        'just','more','very','can','about','up','out','than','then','been','had',
        'get','got','one','two','like','much','some','any','other','there','here',
    ]);

    $insights = [];

    foreach ($questions as $idx => $q) {
        if (!in_array($q['type'], ['text_short', 'text_long'], true)) continue;

        $answers = $q['text_answers'] ?? [];
        if (count($answers) < 5) continue;

        $word_counts = [];
        $total       = count($answers);

        foreach ($answers as $answer) {
            // Tokenise: lowercase, strip punctuation, split on whitespace/hyphens
            $words = preg_split('/[\s\-]+/', strtolower(preg_replace('/[^a-z0-9\s\-]/i', '', $answer)));
            $seen  = [];
            foreach ($words as $word) {
                if (strlen($word) < 4) continue;
                if (isset($stop_words[$word])) continue;
                if (isset($seen[$word])) continue;
                $seen[$word]           = true;
                $word_counts[$word]    = ($word_counts[$word] ?? 0) + 1;
            }
        }

        if (empty($word_counts)) continue;

        arsort($word_counts);
        $top_word  = array_key_first($word_counts);
        $top_count = $word_counts[$top_word];
        $pct       = $top_count / $total;

        if ($pct >= 0.40) {
            $rounded_pct = round($pct * 100);
            $q_lbl       = $q['label'];
            $insights[]  = [
                'text'  => "\"{$top_word}\" came up in {$rounded_pct}% of responses to \"{$q_lbl}\".",
                'score' => $pct,
            ];
        }
    }

    return $insights;
}

$generators[] = 'insight_text_terms';

// ── Runner ────────────────────────────────────────────────────────────────
//
// Runs all registered generators, merges results, and returns the top
// $limit insights sorted by score (strength of the signal).

function compute_insights(array $questions, PDO $db, int $submission_count, array $generators, int $limit = 3): array
{
    if ($submission_count < 5) return [];

    $all = [];
    foreach ($generators as $fn) {
        foreach ($fn($questions, $db, $submission_count) as $insight) {
            $all[] = $insight;
        }
    }

    usort($all, fn($a, $b) => $b['score'] <=> $a['score']);

    return array_slice($all, 0, $limit);
}
