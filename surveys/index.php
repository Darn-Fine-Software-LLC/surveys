<?php
session_start();

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
    $page_title = 'Survey Not Found';
    $not_found  = true;
} else {
    $not_found = false;

    $stmt = $db->prepare(
        'SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order'
    );
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
    }
    unset($q);

    $expires_at  = (int)$survey['expires_at'];
    $page_title  = htmlspecialchars($survey['title']) . ' — Darn Fine Surveys';
}

$success = $_SESSION['survey_submitted'] ?? false;
unset($_SESSION['survey_submitted']);
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($not_found): ?>
    <title>Survey Not Found — Darn Fine Surveys</title>
    <meta name="description" content="This survey doesn't exist or has already expired.">
    <meta property="og:title" content="Survey Not Found — Darn Fine Surveys">
    <meta property="og:description" content="This survey doesn't exist or has already expired.">
    <?php else: ?>
    <title>Submit a response to our survey — Darn Fine Surveys</title>
    <meta name="description" content="<?= htmlspecialchars($survey['title']) ?>">
    <meta property="og:title" content="Submit a response to our survey — Darn Fine Surveys">
    <meta property="og:description" content="<?= htmlspecialchars($survey['title']) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="//unpkg.com/alpinejs" defer></script>
    <link rel="stylesheet" type="text/css" href="/css/style.css">
</head>
<body>

<header class="site-header">
    <h1>Surveys Without The Bull</h1>
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
        <a href="/surveys/results.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-secondary">View Results</a>
    </div>

    <div class="survey-disclaimer">
        Make sure to copy this link, if you lose it, ya lose it. <br/>
        Results are visible to anyone. Don't submit private or sensitive information.
    </div>

    <?php if ($success): ?>
    <div class="alert-success">
        Response submitted. Thanks!
    </div>
    <?php endif; ?>

    <form action="/surveys/submit.php" method="POST">
        <input type="hidden" name="survey_id" value="<?= htmlspecialchars($id) ?>">

        <?php foreach ($questions as $qi => $q): ?>
        <div class="question-block"
             x-data="{ answered: false, dimmed: false }"
             x-on:change="answered = true"
             x-on:input="answered = true"
             x-on:mouseleave="if (answered) dimmed = true"
             x-on:mouseenter="dimmed = false"
             :class="{ 'question-dimmed': dimmed }">

            <div class="question-header">
                <span class="question-number">Question <?= $qi + 1 ?></span>
                <?php if ($q['is_required']): ?>
                <span class="required-badge">Required</span>
                <?php endif; ?>
            </div>

            <div class="question-label">
                <?= htmlspecialchars($q['label']) ?>
                <?php if ($q['is_required']): ?><span class="required-star">*</span><?php endif; ?>
            </div>

            <?php if ($q['description'] !== null && $q['description'] !== ''): ?>
            <div class="question-description"><?= htmlspecialchars($q['description']) ?></div>
            <?php endif; ?>

            <?php if ($q['type'] === 'radio'): ?>
                <div class="choices-list">
                    <?php foreach ($q['choices'] as $choice): ?>
                    <label class="choice-option">
                        <input type="radio"
                               name="answers[<?= $q['id'] ?>]"
                               value="<?= htmlspecialchars($choice['label']) ?>"
                               <?= $q['is_required'] ? 'required' : '' ?>>
                        <span><?= htmlspecialchars($choice['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($q['type'] === 'checkbox'): ?>
                <div class="choices-list">
                    <?php foreach ($q['choices'] as $choice): ?>
                    <label class="choice-option">
                        <input type="checkbox"
                               name="answers[<?= $q['id'] ?>][]"
                               value="<?= htmlspecialchars($choice['label']) ?>">
                        <span><?= htmlspecialchars($choice['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($q['type'] === 'select'): ?>
                <div class="field">
                    <select name="answers[<?= $q['id'] ?>]" <?= $q['is_required'] ? 'required' : '' ?>>
                        <option value="">— Choose one —</option>
                        <?php foreach ($q['choices'] as $choice): ?>
                        <option value="<?= htmlspecialchars($choice['label']) ?>">
                            <?= htmlspecialchars($choice['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            <?php elseif ($q['type'] === 'text_short'): ?>
                <div class="field">
                    <input type="text"
                           name="answers[<?= $q['id'] ?>]"
                           maxlength="255"
                           placeholder="Your answer"
                           <?= $q['is_required'] ? 'required' : '' ?>>
                </div>

            <?php elseif ($q['type'] === 'text_long'): ?>
                <div class="field">
                    <textarea name="answers[<?= $q['id'] ?>]"
                              maxlength="1000"
                              rows="4"
                              placeholder="Your answer"
                              <?= $q['is_required'] ? 'required' : '' ?>></textarea>
                </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

        <div class="submit-row">
            <button type="submit" class="btn btn-primary">Submit Response &rarr;</button>
        </div>

    </form>

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

<footer class="site-footer">
    Created by <a href="https://darnfinesoftware.com">Darn Fine Software</a> in Ohio
    <span class="footer-sep">&middot;</span>
    <a href="https://github.com/Darn-Fine-Software-LLC/surveys">View source</a>
    <span class="footer-sep">&middot;</span>
    <a href="mailto:hi@thatalexguy.dev">Contact us</a>
</footer>

</body>
</html>
