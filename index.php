<?php
$active_surveys  = 0;
$total_responses = 0;
$popular_surveys = [];
$db_path = __DIR__ . '/database/database.sqlite';
if (file_exists($db_path)) {
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try { $db->exec('ALTER TABLE surveys ADD COLUMN show_on_home INTEGER NOT NULL DEFAULT 0 CHECK(show_on_home IN (0,1))'); } catch (Exception $e) {}
        $active_surveys  = (int)$db->query('SELECT COUNT(*) FROM surveys WHERE expires_at > ' . time())->fetchColumn();
        $total_responses = (int)$db->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
        $popular_surveys = $db->query('
            SELECT s.id, s.title, s.created_at,
                   (SELECT COUNT(*) FROM questions q WHERE q.survey_id = s.id) AS question_count,
                   (SELECT COUNT(*) FROM submissions sub WHERE sub.survey_id = s.id) AS response_count
            FROM surveys s
            WHERE s.expires_at > ' . time() . ' AND s.show_on_home = 1
            ORDER BY response_count DESC
            LIMIT 10
        ')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* db not ready yet */ }
}
?>
<?php include __DIR__ . '/components/header.php'; ?>

<main>
    <section class="hero">
        <span class="hero-eyebrow">
            <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
            Surveys Without The Bull
        </span>
        <h1>Create a survey in<br><em>seconds</em></h1>
        <p class="hero-subtitle">No account needed. Share the link. Collect responses. Surveys auto-delete when they expire. Results are always public.</p>
        <div class="hero-actions">
            <a href="#create" class="btn btn-primary btn-lg">Create Your Survey</a>
            <?php if (!empty($popular_surveys)): ?>
            <a href="/surveys/browse.php" class="btn btn-secondary btn-lg">Browse Surveys</a>
            <?php endif; ?>
        </div>
    </section>

    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-number"><?= htmlspecialchars((string)$active_surveys) ?></span>
            <span class="stat-label">Active Surveys</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= htmlspecialchars((string)$total_responses) ?></span>
            <span class="stat-label">Total Responses</span>
        </div>
    </div>

    <?php if (!empty($popular_surveys)): ?>
    <section class="popular-section" id="popular">
        <div class="section-header">
            <h2 class="section-title">Popular Surveys</h2>
            <div class="section-nav">
                <button class="btn btn-secondary" onclick="scrollPopular(-1)" aria-label="Previous">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 11L5 7l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button class="btn btn-secondary" onclick="scrollPopular(1)" aria-label="Next">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
        <div class="popular-scroll" id="popularScroll">
            <?php foreach ($popular_surveys as $s): ?>
            <a href="/surveys?id=<?= htmlspecialchars($s['id']) ?>" class="popular-card">
                <div class="popular-card-header">
                    <span class="popular-card-date"><?= date('M j, Y', (int)$s['created_at']) ?></span>
                </div>
                <div class="popular-card-title"><?= htmlspecialchars($s['title']) ?></div>
                <div class="popular-card-meta">
                    <span class="popular-meta-item">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 6a4 4 0 1 0 8 0 4 4 0 0 0-8 0z" stroke="currentColor" stroke-width="1.2"/><circle cx="6" cy="6" r="1" fill="currentColor"/></svg>
                        <?= (int)$s['response_count'] ?> <?= $s['response_count'] == 1 ? 'response' : 'responses' ?>
                    </span>
                    <span class="popular-meta-item">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 3h8M2 6h6M2 9h7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                        <?= (int)$s['question_count'] ?> <?= $s['question_count'] == 1 ? 'question' : 'questions' ?>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
            <a href="/surveys/browse.php" class="popular-card popular-card-explore">
                <div class="explore-icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 3v14M3 10h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div class="explore-label">Explore More</div>
                <div class="explore-sub">View all surveys</div>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <section id="create">
        <div class="section-intro">
            <h2>Create a Survey</h2>
            <p>Fill in the details, add your questions, and share the link.</p>
        </div>

        <form action="/surveys/create.php" method="POST" x-data="surveyBuilder()" @submit="prepareSubmit">

            <div class="form-card">
                <div class="field">
                    <label for="title">Survey Title</label>
                    <input id="title" required type="text" name="title" placeholder="e.g. Team Lunch Preferences" maxlength="255">
                </div>
                <div class="field">
                    <label for="expiration">Auto-delete after <span class="hint">(survey expires and is deleted)</span></label>
                    <select id="expiration" required name="expiration_length">
                        <option value="1">1 Day</option>
                        <option value="7">1 Week</option>
                        <option value="31">1 Month</option>
                    </select>
                </div>
                <div class="field-check">
                    <input type="checkbox" id="show_on_home" name="show_on_home" value="1">
                    <span>Feature this survey publicly<br><span class="hint" style="font-size:0.75rem">Lets others discover your survey on the homepage</span></span>
                </div>
            </div>

            <div id="survey-questions">
                <template x-for="(question, qi) in questions" :key="qi">
                    <div class="question-block">
                        <div class="question-header">
                            <span class="question-number" x-text="'Question ' + (qi + 1)"></span>
                            <button type="button" class="btn btn-ghost btn-sm" @click="removeQuestion(qi)">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                Remove
                            </button>
                        </div>

                        <div class="field">
                            <label>Question</label>
                            <input required type="text"
                                :name="'questions[' + qi + '][label]'"
                                x-model="question.label"
                                placeholder="e.g. What is your favorite color?">
                        </div>

                        <div class="field">
                            <label>Description <span class="hint">(optional)</span></label>
                            <input type="text"
                                :name="'questions[' + qi + '][description]'"
                                x-model="question.description"
                                placeholder="Any extra context for this question">
                        </div>

                        <div class="field">
                            <label>Answer Type</label>
                            <select :name="'questions[' + qi + '][type]'" x-model="question.type">
                                <option value="pick_one">Pick One</option>
                                <option value="checkbox">Pick Multiple</option>
                                <option value="text_short">Short Text</option>
                                <option value="text_long">Long Text</option>
                            </select>
                        </div>

                        <div class="field-check">
                            <input type="checkbox"
                                :name="'questions[' + qi + '][required]'"
                                :id="'required_' + qi"
                                value="1"
                                x-model="question.required">
                            <span>Required <span class="hint">(respondent must answer)</span></span>
                        </div>

                        <div class="choices-section" x-show="needsChoices(question.type)">
                            <div class="choices-label">Answer Choices</div>
                            <template x-for="(choice, ci) in question.choices" :key="ci">
                                <div class="choice-row">
                                    <input type="text"
                                        :required="needsChoices(question.type)"
                                        :name="'questions[' + qi + '][choices][' + ci + ']'"
                                        x-model="question.choices[ci]"
                                        :placeholder="'Choice ' + (ci + 1)">
                                    <button type="button" class="btn btn-ghost"
                                        @click="removeChoice(qi, ci)"
                                        x-show="question.choices.length > 2"
                                        title="Remove choice">
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" class="btn btn-ghost btn-sm" @click="addChoice(qi)" style="margin-top:0.5rem">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                Add Choice
                            </button>
                        </div>
                    </div>
                </template>

                <div class="empty-state" x-show="questions.length === 0">
                    No questions yet — add one below.
                </div>
            </div>

            <div class="add-question-row">
                <button type="button" class="btn btn-secondary" @click="addQuestion()">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    Add Question
                </button>
            </div>

            <div class="submit-row">
                <button type="submit" class="btn btn-primary btn-lg" :disabled="questions.length === 0">
                    Create Survey
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>

        </form>
    </section>
</main>

<script>
    function scrollPopular(dir) {
        const el = document.getElementById('popularScroll');
        if (el) el.scrollBy({ left: dir * 280, behavior: 'smooth' });
    }

    function surveyBuilder() {
        return {
            questions: [],

            addQuestion() {
                this.questions.push({
                    label: '',
                    description: '',
                    type: 'pick_one',
                    required: false,
                    choices: ['', '']
                });
            },

            removeQuestion(index) {
                this.questions.splice(index, 1);
            },

            addChoice(questionIndex) {
                this.questions[questionIndex].choices.push('');
            },

            removeChoice(questionIndex, choiceIndex) {
                this.questions[questionIndex].choices.splice(choiceIndex, 1);
            },

            needsChoices(type) {
                return ['checkbox', 'pick_one'].includes(type);
            },

            prepareSubmit(e) {
                if (this.questions.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one question.');
                }
            }
        }
    }
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
