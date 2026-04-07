<?php
$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    http_response_code(400);
    exit;
}

$stmt = $db->prepare('SELECT * FROM surveys WHERE id = ?');
$stmt->execute([$id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey || $survey['expires_at'] < time()) {
    http_response_code(404);
    exit;
}

$stmt = $db->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order');
$stmt->execute([$id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT * FROM submissions WHERE survey_id = ? ORDER BY submitted_at ASC');
$stmt->execute([$id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$choice_types = ['radio', 'checkbox', 'select'];

foreach ($questions as &$q) {
    if (in_array($q['type'], $choice_types, true)) {
        $stmt = $db->prepare(
            'SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order'
        );
        $stmt->execute([$q['id']]);
        $q['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
unset($q);

$lines = [];
$header = ['Submission Date'];
foreach ($questions as $q) {
    $header[] = '"' . str_replace('"', '""', $q['label']) . '"';
}
$lines[] = implode(',', $header);

foreach ($submissions as $sub) {
    $row = [date('Y-m-d H:i:s', (int)$sub['submitted_at'])];

    $stmt = $db->prepare('SELECT * FROM answers WHERE submission_id = ?');
    $stmt->execute([$sub['id']]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $answer_map = [];
    foreach ($answers as $a) {
        $answer_map[$a['question_id']] = $a['value'];
    }

    foreach ($questions as $q) {
        $val = $answer_map[$q['id']] ?? '';
        if ($q['type'] === 'checkbox' && $val !== '') {
            $selected = json_decode($val, true) ?? [];
            $val = implode('; ', $selected);
        }
        $row[] = '"' . str_replace('"', '""', $val) . '"';
    }
    $lines[] = implode(',', $row);
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="survey-' . $id . '.csv"');
echo implode("\n", $lines);
