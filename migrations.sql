CREATE TABLE IF NOT EXISTS surveys (
    id          TEXT    PRIMARY KEY,   -- random unique short ID
    title       TEXT    NOT NULL,
    created_at  INTEGER NOT NULL,      -- unix timestamp
    expires_at  INTEGER NOT NULL       -- unix timestamp
);

CREATE TABLE IF NOT EXISTS questions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id   TEXT    NOT NULL REFERENCES surveys(id) ON DELETE CASCADE,
    label       TEXT    NOT NULL,
    description TEXT,
    type        TEXT    NOT NULL CHECK(type IN ('radio','checkbox','select','text_short','text_long')),
    is_required INTEGER NOT NULL DEFAULT 0 CHECK(is_required IN (0,1)),
    sort_order  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS question_choices (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    label       TEXT    NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0
);

-- One row per form submission (groups answers together)
CREATE TABLE IF NOT EXISTS submissions (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id    TEXT    NOT NULL REFERENCES surveys(id) ON DELETE CASCADE,
    submitted_at INTEGER NOT NULL   -- unix timestamp
);

-- One row per question answer within a submission.
-- For checkbox questions, value is a JSON array of selected choice labels.
-- For all other types, value is a plain text string.
CREATE TABLE IF NOT EXISTS answers (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    question_id   INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    value         TEXT    NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_questions_survey_id       ON questions(survey_id);
CREATE INDEX IF NOT EXISTS idx_question_choices_question ON question_choices(question_id);
CREATE INDEX IF NOT EXISTS idx_submissions_survey_id     ON submissions(survey_id);
CREATE INDEX IF NOT EXISTS idx_answers_submission_id     ON answers(submission_id);
CREATE INDEX IF NOT EXISTS idx_answers_question_id       ON answers(question_id);
