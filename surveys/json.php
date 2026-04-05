<?php
$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing survey id']);
    exit;
}

$stmt = $db->prepare('SELECT * FROM surveys WHERE id = ?');
$stmt->execute([$id]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$survey || $survey['expires_at'] < time()) {
    http_response_code(404);
    echo json_encode(['error' => 'Survey not found or expired']);
    exit;
}

$stmt = $db->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order');
$stmt->execute([$id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output_questions = [];
foreach ($questions as $q) {
    $stmt = $db->prepare(
        'SELECT a.value, s.submitted_at FROM answers a
         JOIN submissions s ON s.id = a.submission_id
         WHERE a.question_id = ?
         ORDER BY s.submitted_at ASC'
    );
    $stmt->execute([$q['id']]);
    $raw_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $responses = [];
    foreach ($raw_answers as $ans) {
        $value = $q['type'] === 'checkbox'
            ? json_decode($ans['value'], true)
            : $ans['value'];
        $responses[] = [
            'date'  => date('c', (int)$ans['submitted_at']),
            'value' => $value,
        ];
    }

    $output_questions[] = [
        'label'       => $q['label'],
        'description' => $q['description'],
        'is_required' => (bool)$q['is_required'],
        'type'        => $q['type'],
        'responses'   => $responses,
    ];
}

$payload = [
    'title'      => $survey['title'],
    'created_at' => date('c', (int)$survey['created_at']),
    'expires_at' => date('c', (int)$survey['expires_at']),
    'questions'  => $output_questions,
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="survey-' . $id . '.json"');
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
