<?php
$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    header('Location: /');
    exit;
}

$stmt = $db->prepare('SELECT * FROM surveys WHERE id = ?');
$stmt->execute([$id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

$now = time();

if (!$survey || $survey['expires_at'] < $now) {
    http_response_code(404);
    $not_found = true;
} else {
    $not_found = false;

    $stmt = $db->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order');
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $choice_types = ['radio', 'checkbox', 'select'];

    foreach ($questions as &$q) {
        if (in_array($q['type'], $choice_types, true)) {
            $stmt = $db->prepare(
                'SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order'
            );
            $stmt->execute([$q['id']]);
            $q['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $q['choices'] = [];
        }

        $stmt = $db->prepare(
            'SELECT a.value FROM answers a
             JOIN submissions s ON s.id = a.submission_id
             WHERE a.question_id = ?
             ORDER BY s.submitted_at DESC'
        );
        $stmt->execute([$q['id']]);
        $raw_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (in_array($q['type'], $choice_types, true)) {
            $counts = [];
            foreach ($q['choices'] as $choice) {
                $counts[$choice['label']] = 0;
            }
            foreach ($raw_answers as $ans) {
                if ($q['type'] === 'checkbox') {
                    $selected = json_decode($ans['value'], true) ?? [];
                    foreach ($selected as $val) {
                        $counts[$val] = ($counts[$val] ?? 0) + 1;
                    }
                } else {
                    $val = $ans['value'];
                    $counts[$val] = ($counts[$val] ?? 0) + 1;
                }
            }
            $q['counts'] = $counts;
            $q['response_count'] = count($raw_answers);
        } else {
            $q['text_answers'] = array_column($raw_answers, 'value');
            $q['response_count'] = count($raw_answers);
        }
    }
    unset($q);

    $expires_at = (int)$survey['expires_at'];
}

$header_cta = true;
include __DIR__ . '/../components/header.php';
?>

<main>

<?php if ($not_found): ?>

    <div class="not-found">
        <div class="not-found-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M6 6l16 16M22 6L6 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <h2>Survey Not Found</h2>
        <p>This survey doesn't exist or has already expired and been deleted.</p>
        <a href="/" class="btn btn-primary">Create a New Survey</a>
    </div>

<?php else: ?>

    <div class="survey-hero" style="padding-bottom:1rem">
        <h1><?= htmlspecialchars($survey['title']) ?></h1>
        <div class="survey-countdown"
             x-data="countdown(<?= $expires_at ?>)"
             x-init="init()">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M6 3v3.5l2 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Deletes in <strong x-text="formatted"></strong>
        </div>
    </div>

    <div class="survey-meta-bar">
        <div class="survey-meta-left">
            <div class="response-count"><?= count($questions) ?> question<?= count($questions) != 1 ? 's' : '' ?></div>
        </div>
        <div class="survey-meta-actions">
            <a href="/surveys?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary btn-sm">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M1 6.5S3 2 6.5 2 12 6.5 12 6.5 10 11 6.5 11 1 6.5 1 6.5z" stroke="currentColor" stroke-width="1.3"/><circle cx="6.5" cy="6.5" r="1.5" stroke="currentColor" stroke-width="1.3"/></svg>
                Take Survey
            </a>
            <a href="/surveys/json.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary btn-sm">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3h2M2 6h2M2 9h2M6 3h5M6 6h5M6 9h5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                JSON
            </a>
            <a href="/surveys/csv.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary btn-sm">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3h2M2 6h2M2 9h2M6 3h5M6 6h5M6 9h5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                CSV
            </a>
        </div>
    </div>

    <div x-data="insightsLoader('<?= htmlspecialchars($id) ?>')" x-init="load()">
        <div class="insights-section" x-show="insights.length > 0" style="display:none">
            <span class="insights-label">Darn Fine Insights</span>
            <template x-for="insight in insights" :key="insight.text">
                <div class="insight-card">
                    <svg class="insight-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 1l1.8 3.6 4 .6-2.9 2.8.7 4L8 10.1 4.4 12l.7-4L2.2 5.2l4-.6z" fill="currentColor"/>
                    </svg>
                    <p class="insight-text" x-text="insight.text"></p>
                </div>
            </template>
        </div>
    </div>

    <?php foreach ($questions as $qi => $q): ?>
    <div class="question-result-block" style="animation-delay: <?= $qi * 0.06 ?>s">

        <div class="question-header">
            <span class="question-number">Question <?= $qi + 1 ?></span>
            <span class="response-count"><?= $q['response_count'] ?> <?= $q['response_count'] === 1 ? 'response' : 'responses' ?></span>
        </div>

        <div class="question-label"><?= htmlspecialchars($q['label']) ?></div>

        <?php if ($q['description'] !== null && $q['description'] !== ''): ?>
        <div class="question-description"><?= htmlspecialchars($q['description']) ?></div>
        <?php endif; ?>

        <?php if (in_array($q['type'], ['radio', 'checkbox', 'select'])): ?>
            <?php if ($q['response_count'] === 0): ?>
                <div class="no-responses">No responses yet.</div>
            <?php else: ?>
                <div class="result-bars">
                    <?php $max = max(1, max(array_values($q['counts']))); ?>
                    <?php foreach ($q['counts'] as $label => $count): ?>
                    <?php $pct = $q['response_count'] > 0 ? round($count / $q['response_count'] * 100) : 0; ?>
                    <div class="result-row">
                        <div class="result-label"><?= htmlspecialchars($label) ?></div>
                        <div class="result-bar-wrap">
                            <div class="result-bar" style="width: <?= $count > 0 ? round($count / $max * 100) : 0 ?>%"></div>
                        </div>
                        <div class="result-stat"><?= $count ?> <span class="result-pct">(<?= $pct ?>%)</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <?php if (empty($q['text_answers'])): ?>
                <div class="no-responses">No responses yet.</div>
            <?php else: ?>
                <div class="text-grid">
                    <?php foreach ($q['text_answers'] as $answer): ?>
                    <div class="text-card"><?= htmlspecialchars($answer) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <?php if (empty($questions)): ?>
    <div class="empty-state">This survey has no questions.</div>
    <?php endif; ?>

<?php endif; ?>

</main>

<script>
    function insightsLoader(id) {
        return {
            insights: [],
            load() {
                fetch('/surveys/insights-api.php?id=' + encodeURIComponent(id))
                    .then(r => r.json())
                    .then(data => { this.insights = data; })
                    .catch(() => {});
            }
        };
    }

    function countdown(expiresAt) {
        return {
            remaining: expiresAt - Math.floor(Date.now() / 1000),
            get formatted() {
                if (this.remaining <= 0) return 'expired';
                const d = Math.floor(this.remaining / 86400);
                const h = Math.floor((this.remaining % 86400) / 3600);
                const m = Math.floor((this.remaining % 3600) / 60);
                const s = this.remaining % 60;
                if (d > 0) return `${d}d ${h}h ${m}m`;
                if (h > 0) return `${h}h ${m}m ${s}s`;
                return `${m}m ${s}s`;
            },
            init() {
                setInterval(() => { this.remaining = Math.max(0, this.remaining - 1); }, 1000);
            }
        };
    }
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
