<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$survey_id   = trim($_POST['survey_id'] ?? '');
$answers_raw = $_POST['answers'] ?? [];

if ($survey_id === '') {
    header('Location: /');
    exit;
}

// Verify survey exists and hasn't expired
$stmt = $db->prepare('SELECT * FROM surveys WHERE id = ?');
$stmt->execute([$survey_id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey || $survey['expires_at'] < time()) {
    header('Location: /');
    exit;
}

// Fetch questions
$stmt = $db->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order');
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validate required questions are answered
foreach ($questions as $q) {
    if (!$q['is_required']) {
        continue;
    }
    $qid = (string)$q['id'];
    if ($q['type'] === 'checkbox') {
        if (empty($answers_raw[$qid])) {
            // Required checkbox with nothing selected — bounce back
            header('Location: /surveys?id=' . urlencode($survey_id));
            exit;
        }
    } else {
        if (!isset($answers_raw[$qid]) || trim((string)$answers_raw[$qid]) === '') {
            header('Location: /surveys?id=' . urlencode($survey_id));
            exit;
        }
    }
}

// Insert submission and answers
$db->beginTransaction();

$stmt = $db->prepare('INSERT INTO submissions (survey_id, submitted_at) VALUES (?, ?)');
$stmt->execute([$survey_id, time()]);
$submission_id = (int)$db->lastInsertId();

foreach ($questions as $q) {
    $qid = (string)$q['id'];

    if ($q['type'] === 'checkbox') {
        $selected = isset($answers_raw[$qid]) && is_array($answers_raw[$qid])
            ? array_values(array_filter(array_map('trim', $answers_raw[$qid])))
            : [];
        $value = json_encode($selected);
    } else {
        $value = trim((string)($answers_raw[$qid] ?? ''));
    }

    if ($value === '' || $value === '[]') {
        // Skip unanswered optional questions
        continue;
    }

    $stmt = $db->prepare(
        'INSERT INTO answers (submission_id, question_id, value) VALUES (?, ?, ?)'
    );
    $stmt->execute([$submission_id, $q['id'], $value]);
}

$db->commit();

$_SESSION['survey_submitted'] = true;
header('Location: /surveys?id=' . urlencode($survey_id));
exit;
