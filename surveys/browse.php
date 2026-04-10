<?php
$page_title = "Browse Surveys — Darn Fine Surveys";
$page_description = "Explore all public surveys. No account needed.";
$db_path = __DIR__ . "/../database/database.sqlite";

$surveys = [];
if (file_exists($db_path)) {
    try {
        $db = new PDO("sqlite:" . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $surveys = $db
            ->query(
                '
            SELECT s.id, s.title, s.created_at,
                   (SELECT COUNT(*) FROM questions q WHERE q.survey_id = s.id) AS question_count,
                   (SELECT COUNT(*) FROM submissions sub WHERE sub.survey_id = s.id) AS response_count
            FROM surveys s
            WHERE s.expires_at > ' .
                    time() .
                    '
            AND s.show_on_home = 1
            ORDER BY s.created_at DESC
        ',
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        /* db not ready */
    }
}
?>
<?php include __DIR__ . "/../components/header.php"; ?>

<main>
    <section class="browse-hero">
        <h1>Browse Surveys</h1>
        <p class="browse-subtitle">Participate in surveys from other users!</p>
    </section>

    <?php if (empty($surveys)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="10" fill="var(--accent-soft)"/>
            <path d="M12 15h16M12 20h12M12 25h8" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p>No surveys yet. <a href="/">Create one</a> to get started.</p>
    </div>
    <?php else: ?>
    <section class="browse-grid">
        <?php foreach ($surveys as $i => $s): ?>
        <a href="/surveys?id=<?= htmlspecialchars(
            $s["id"],
        ) ?>" class="browse-card" style="animation-delay: <?= $i * 40 ?>ms">
            <div class="browse-card-header">
                <span class="browse-card-date"><?= date(
                    "M j, Y",
                    (int) $s["created_at"],
                ) ?></span>
            </div>
            <div class="browse-card-title"><?= htmlspecialchars(
                $s["title"],
            ) ?></div>
            <div class="browse-card-meta">
                <span class="browse-meta-item">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 6a4 4 0 1 0 8 0 4 4 0 0 0-8 0z" stroke="currentColor" stroke-width="1.2"/><circle cx="6" cy="6" r="1" fill="currentColor"/></svg>
                    <?= (int) $s["response_count"] ?> <?= $s[
     "response_count"
 ] == 1
     ? "response"
     : "responses" ?>
                </span>
                <span class="browse-meta-item">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 3h8M2 6h6M2 9h7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                    <?= (int) $s["question_count"] ?> <?= $s[
     "question_count"
 ] == 1
     ? "question"
     : "questions" ?>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
</main>

<style>
.browse-hero {
    text-align: center;
    padding: 2.5rem 0 2rem;
    animation: fade-up 0.5s ease both;
}

.browse-hero h1 {
    font-family: var(--font-display);
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.browse-subtitle {
    color: var(--muted);
    font-size: 1rem;
    max-width: 420px;
    margin: 0 auto;
    line-height: 1.65;
}

.browse-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.browse-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.4rem;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    box-shadow: var(--shadow-sm);
    transition:
        box-shadow 0.2s,
        transform 0.2s,
        border-color 0.2s;
    animation: fade-up 0.4s ease both;
}

.browse-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: var(--accent-mid);
}

.browse-card-header {
    display: flex;
    justify-content: flex-end;
}

.browse-card-date {
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    color: var(--muted-light);
    text-transform: uppercase;
}

.browse-card-title {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.1rem;
    line-height: 1.35;
    color: var(--text);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}

.browse-card-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.775rem;
    color: var(--muted);
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-soft);
}

.browse-meta-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.browse-meta-item svg {
    color: var(--muted-light);
}

.empty-state svg {
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--muted);
    font-size: 0.95rem;
}

.empty-state a {
    color: var(--accent);
    text-decoration: underline;
    text-underline-offset: 2px;
}

@media (max-width: 600px) {
    .browse-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . "/../components/footer.php"; ?>
