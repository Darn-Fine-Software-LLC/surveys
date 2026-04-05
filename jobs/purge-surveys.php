<?php
/**
 * Purge expired surveys.
 *
 * Usage:
 *   php jobs/purge-surveys.php [--dry-run]
 *
 * Options:
 *   --dry-run   Report what would be deleted without deleting anything.
 */

$dry_run = in_array('--dry-run', $argv, true);

$db_path = __DIR__ . '/../database/database.sqlite';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys = ON');

$now = time();

$stmt = $db->prepare('SELECT id, title, expires_at FROM surveys WHERE expires_at < ?');
$stmt->execute([$now]);
$expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($expired)) {
    echo "No expired surveys found.\n";
    exit(0);
}

foreach ($expired as $survey) {
    $age = $now - $survey['expires_at'];
    printf(
        "%s  id=%-12s  expired %s ago  title=%s\n",
        $dry_run ? '[DRY RUN]' : '[DELETED]',
        $survey['id'],
        format_duration($age),
        $survey['title']
    );
}

if (!$dry_run) {
    $ids = array_column($expired, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("DELETE FROM surveys WHERE id IN ($placeholders)")->execute($ids);
}

printf(
    "%s %d expired survey(s).\n",
    $dry_run ? 'Would delete' : 'Deleted',
    count($expired)
);

exit(0);

function format_duration(int $seconds): string
{
    if ($seconds < 60)      return "{$seconds}s";
    if ($seconds < 3600)    return floor($seconds / 60) . 'm';
    if ($seconds < 86400)   return floor($seconds / 3600) . 'h';
    return floor($seconds / 86400) . 'd';
}
