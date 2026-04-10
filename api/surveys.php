<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if ($body === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

// --- Validate ---
$errors = [];

$title = trim($body['title'] ?? '');
if ($title === '') {
    $errors[] = 'title is required.';
} elseif (strlen($title) > 255) {
    $errors[] = 'title must be 255 characters or fewer.';
}

$expiration_days = $body['expiration_days'] ?? null;
$allowed_days = [1, 7, 31];
if (!is_int($expiration_days)) {
    $errors[] = 'expiration_days must be an integer.';
} elseif (!in_array($expiration_days, $allowed_days, true)) {
    $errors[] = 'expiration_days must be one of: 1, 7, or 31.';
}

$show_on_home = isset($body['show_on_home']) && $body['show_on_home'] === true ? 1 : 0;

$questions_raw = $body['questions'] ?? [];
$valid_types   = ['radio', 'checkbox', 'select', 'text_short', 'text_long'];
$choice_types  = ['radio', 'checkbox', 'select'];

if (!is_array($questions_raw) || count($questions_raw) === 0 || !array_is_list($questions_raw)) {
    $errors[] = 'questions must be a non-empty array.';
} else {
    foreach ($questions_raw as $i => $q) {
        $num = $i + 1;

        $label = trim($q['label'] ?? '');
        if ($label === '') {
            $errors[] = "questions[$i].label is required.";
        } elseif (strlen($label) > 255) {
            $errors[] = "questions[$i].label must be 255 characters or fewer.";
        }

        $type = $q['type'] ?? '';
        if (!in_array($type, $valid_types, true)) {
            $errors[] = "questions[$i].type must be one of: " . implode(', ', $valid_types) . '.';
        }

        if (in_array($type, $choice_types, true)) {
            if (!is_array($q['choices'] ?? null) || !array_is_list($q['choices'])) {
                $errors[] = "questions[$i].choices must be an array.";
            } else {
                $raw_choices = $q['choices'];
                $choices = array_filter(array_map('trim', $raw_choices), fn($c) => $c !== '');
                if (count($choices) < 2) {
                    $errors[] = "questions[$i].choices must contain at least 2 non-empty options.";
                }
            }
        }
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['errors' => $errors]);
    exit;
}

// --- Database ---
$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function generate_id(PDO $db): string {
    do {
        $id = bin2hex(random_bytes(5));
        $stmt = $db->prepare('SELECT 1 FROM surveys WHERE id = ?');
        $stmt->execute([$id]);
    } while ($stmt->fetchColumn() !== false);
    return $id;
}

$id         = generate_id($db);
$now        = time();
$expires_at = $now + ((int)$expiration_days * 86400);

// --- Insert (transaction) ---
try {
    $db->beginTransaction();

    $stmt = $db->prepare('INSERT INTO surveys (id, title, created_at, expires_at, show_on_home) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$id, $title, $now, $expires_at, $show_on_home]);

    foreach (array_values($questions_raw) as $sort_order => $q) {
        $label       = trim($q['label']);
        $description = trim($q['description'] ?? '') ?: null;
        $type        = $q['type'];
        $is_required = isset($q['required']) && $q['required'] === true ? 1 : 0;

        $stmt = $db->prepare(
            'INSERT INTO questions (survey_id, label, description, type, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $label, $description, $type, $is_required, $sort_order]);
        $question_id = (int)$db->lastInsertId();

        if (in_array($type, $choice_types, true)) {
            $choices = array_values(
                array_filter(array_map('trim', $q['choices']), fn($c) => $c !== '')
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
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create survey.']);
    exit;
}

// --- Respond ---
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$survey_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/surveys?id=' . $id;

http_response_code(201);
echo json_encode(['id' => $id, 'url' => $survey_url]);
