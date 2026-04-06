# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

Darn Fine Surveys — a no-frills PHP/SQLite survey tool. All surveys are public (no auth). Surveys auto-delete after 1 day, 1 week, or 1 month. Results are visible to anyone at any time.

## Running Locally

Requires PHP with PDO SQLite. Serve from the repo root:

```bash
php -S localhost:8000
```

The database lives at `database/database.sqlite`. Create it by running the schema:

```bash
sqlite3 database/database.sqlite < migrations.sql
```

## Purging Expired Surveys

```bash
php jobs/purge-surveys.php           # delete expired surveys
php jobs/purge-surveys.php --dry-run # preview without deleting
```

This should be scheduled as a cron job in production.

## URL Structure

| URL | File |
|-----|------|
| `/` | `index.php` — homepage + create survey form |
| `/surveys?id=XXXXX` | `surveys/index.php` — take a survey |
| `/surveys/results.php?id=XXXXX` | `surveys/results.php` — view results |
| `/surveys/json.php?id=XXXXX` | `surveys/json.php` — download results as JSON |
| `/surveys/create.php` | POST handler — creates survey, redirects to it |
| `/surveys/submit.php` | POST handler — records submission |
| `/surveys/done.php` | `surveys/done.php` — post-submission confirmation |

## Database Schema

Five tables: `surveys`, `questions`, `question_choices`, `submissions`, `answers`. All relationships use `ON DELETE CASCADE` so deleting a survey removes all child data.

- `surveys.id` — 10-char hex string (`bin2hex(random_bytes(5))`)
- `questions.type` — one of `radio`, `checkbox`, `select`, `text_short`, `text_long`
- `answers.value` — JSON array string for `checkbox` type; plain text for all others
- `surveys.show_on_home` — opt-in flag to feature survey on homepage

## Question Type Logic

The create form uses `pick_one` as a UI-only type. `create.php` converts it on save: ≤3 choices → `radio`, >3 choices → `select`.

## Migrations

No migration runner. New columns are added inline in PHP with silent-fail `ALTER TABLE ... ADD COLUMN` (e.g., in `index.php`). Safe to re-run; errors are caught and ignored.

## Frontend

No build step. Alpine.js is loaded from CDN (`//unpkg.com/alpinejs`). All interactivity (dynamic question builder, countdown timers, question dimming after answer) is inline `<script>` in each PHP file. Styles are in `css/style.css`.
