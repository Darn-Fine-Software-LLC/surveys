# Darn Fine Surveys

**Surveys without the bull.** Create a survey in seconds — no account, no setup, no tracking. Share a link. Collect responses. Done.

➡️ **[Try it live at surveys.darnfinesoftware.com](https://surveys.darnfinesoftware.com)**

---

![Home page](screenshots/home-page.png)

## Why Darn Fine Surveys?

A survey tool that's made to be fast and free.

- **No accounts.** Create a survey and share the link, couldn't be easier!
- **Auto-expiring.** Surveys self-destruct after 1 day, 1 week, or 1 month. Prevents database from growing too large.
- **Public results.** Anyone with the link can see responses in real time. Discourages storage of sensitive information.
- **Zero dependencies.** PHP + SQLite. No Composer or Docker needed.
- **Very fast.** No framework overhead or JS mess. Pages load instantly.

---

## Screenshots

| Taking a Survey | Viewing Results |
|---|---|
| ![Survey form](screenshots/survey-form.png) | ![Survey results](screenshots/survey-results.png) |

Results include algorithm generated insights. Export to JSON or CSV with one click.

---

## Self-Hosting

**Requirements:** PHP with PDO SQLite (that's it).

```bash
git clone https://github.com/Darn-Fine-Software-LLC/Darn-Fine-Surveys
cd surveys
sqlite3 database/database.sqlite < migrations.sql
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) and start surveying.

### Purge expired surveys

```bash
php jobs/purge-surveys.php           # delete expired surveys
php jobs/purge-surveys.php --dry-run # preview without deleting
```

Add this to a cron job to run nightly.

---

## Question Types

- **Radio / Select** — pick one (auto-selects radio for ≤3 choices, dropdown for more)
- **Checkbox** — pick multiple
- **Short text** — one-line answer
- **Long text** — paragraph answer

---

## Stack

| Layer | Tech |
|---|---|
| Backend | PHP 8+ |
| Database | SQLite via PDO |
| Frontend | Alpine.js (CDN) |
| Styles | Vanilla CSS |
| Build step | None |

---

## License

[GNU General Public License v3.0](LICENSE.md) — free to use, modify, and self-host.

---

Built and hosted by [Darn Fine Software](https://darnfinesoftware.com). If this saved you from signing up for yet another SaaS, consider leaving a ⭐.
