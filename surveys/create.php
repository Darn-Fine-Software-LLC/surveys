<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// --- Collect input ---
$title             = trim($_POST['title'] ?? '');
$expiration_length = $_POST['expiration_length'] ?? '';
$questions_raw     = $_POST['questions'] ?? [];

$errors = [];

// --- Validate title ---
if ($title === '') {
    $errors[] = 'Survey title is required.';
} elseif (strlen($title) > 255) {
    $errors[] = 'Survey title must be 255 characters or fewer.';
}

// --- Validate expiration ---
$allowed_days = ['1', '7', '31'];
if (!in_array($expiration_length, $allowed_days, true)) {
    $errors[] = 'Please select a valid expiration option.';
}

// --- Validate questions ---
$valid_types = ['radio', 'checkbox', 'select', 'text_short', 'text_long'];
$choice_types = ['radio', 'checkbox', 'select'];

if (!is_array($questions_raw) || count($questions_raw) === 0) {
    $errors[] = 'At least one question is required.';
} else {
    foreach ($questions_raw as $i => $q) {
        $num = $i + 1;

        $label = trim($q['label'] ?? '');
        if ($label === '') {
            $errors[] = "Question $num: label is required.";
        } elseif (strlen($label) > 255) {
            $errors[] = "Question $num: label must be 255 characters or fewer.";
        }

        $type = $q['type'] ?? '';
        if (!in_array($type, $valid_types, true)) {
            $errors[] = "Question $num: invalid type.";
        }

        if (in_array($type, $choice_types, true)) {
            $choices = array_filter(array_map('trim', $q['choices'] ?? []), fn($c) => $c !== '');
            if (count($choices) < 2) {
                $errors[] = "Question $num: at least two answer choices are required.";
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['survey_errors'] = $errors;
    $_SESSION['survey_old']    = $_POST;
    header('Location: /');
    exit;
}

// --- Generate unique ID ---
$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function generate_id(PDO $db): string {
    do {
        $id = bin2hex(random_bytes(5)); // 10 hex chars
        $stmt = $db->prepare('SELECT 1 FROM surveys WHERE id = ?');
        $stmt->execute([$id]);
    } while ($stmt->fetchColumn() !== false);
    return $id;
}

$id = generate_id($db);

// --- Calculate timestamps ---
$now       = time();
$expires_at = $now + ((int)$expiration_length * 86400);

// --- Insert survey (transaction) ---
$db->beginTransaction();

$stmt = $db->prepare('INSERT INTO surveys (id, title, created_at, expires_at) VALUES (?, ?, ?, ?)');
$stmt->execute([$id, $title, $now, $expires_at]);

foreach ($questions_raw as $sort_order => $q) {
    $label       = trim($q['label']);
    $description = trim($q['description'] ?? '') ?: null;
    $type        = $q['type'];
    $is_required = isset($q['required']) && $q['required'] ? 1 : 0;

    $stmt = $db->prepare(
        'INSERT INTO questions (survey_id, label, description, type, is_required, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$id, $label, $description, $type, $is_required, $sort_order]);
    $question_id = (int)$db->lastInsertId();

    if (in_array($type, $choice_types, true)) {
        $choices = array_values(
            array_filter(array_map('trim', $q['choices'] ?? []), fn($c) => $c !== '')
        );
        foreach ($choices as $ci => $choice_label) {
            $stmt = $db->prepare(
                'INSERT INTO question_choices (question_id, label, sort_order) VALUES (?, ?, ?)'
            );
            $stmt->execute([$question_id, $choice_label, $ci]);
        }
    }
}

$db->commit();

header('Location: /surveys?id=' . $id);
exit;
