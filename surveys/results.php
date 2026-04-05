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
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $not_found ? 'Survey Not Found — Darn Fine Surveys' : htmlspecialchars($survey['title']) . ' — Results — Darn Fine Surveys' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="//unpkg.com/alpinejs" defer></script>
    <link rel="stylesheet" type="text/css" href="/css/style.css">
</head>
<body>

<header class="site-header">
    <h1>Surveys Without The Bullshit</h1>
    <p>Couple of clicks, you have a survey. It expires. Results are public — don't ask for anything you'd hide from your neighbor.</p>
    <a href="/" class="btn btn-secondary" style="margin-top: 1rem">Create your own survey!</a>
</header>

<main>

<?php if ($not_found): ?>

    <div class="form-intro">
        <h2>Survey Not Found</h2>
        <p>This survey doesn't exist or has already expired and been deleted.</p>
    </div>
    <div class="submit-row">
        <a href="/" class="btn btn-secondary">Create a New Survey</a>
    </div>

<?php else: ?>

    <div class="survey-meta">
        <div class="survey-meta-left">
            <h2><?= htmlspecialchars($survey['title']) ?></h2>
            <div class="survey-countdown"
                 x-data="countdown(<?= $expires_at ?>)"
                 x-init="init()">
                Deletes in <strong x-text="formatted"></strong>
            </div>
        </div>
        <div class="survey-meta-actions">
            <a href="/surveys?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary">Take Survey</a>
            <a href="/surveys/json.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary">Download JSON</a>
        </div>
    </div>

    <?php foreach ($questions as $qi => $q): ?>
    <div class="question-block">

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

</body>
</html>
