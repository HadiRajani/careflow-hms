# CareFlow HMS (OPD thin slice)

**Not for clinical use.** This is a demo built with synthetic data only. It hasn't been clinically validated or security tested.

A patient arrives at the OPD, gets registered (with a duplicate check), a visit is opened, a nurse records triage and the system flags risky vitals, and the quality manager sees it all on a dashboard. Key screens can be switched to Urdu.

Built with Laravel 12, PHP 8.2, MySQL 8, Blade and Tailwind.

- Live: https://careflow-hms-production.up.railway.app
- Repo: https://github.com/HadiRajani/careflow-hms

Demo logins (password `CareFlow2026!` for all):

- Front desk: `frontdesk@careflow.test`
- Nurse: `nurse@careflow.test`
- Quality manager: `quality@careflow.test`

## Running it locally

You need PHP 8.2+, Composer, Node 20+ and MySQL.

```bash
composer install
npm install && npm run build
cp .env.example .env        # set DB_* and DEMO_PASSWORD
php artisan key:generate
php artisan migrate --seed  # creates tables, demo users and ~600 synthetic visits
php artisan serve
```

Tests run on an in-memory SQLite database, so they don't touch MySQL:

```bash
php artisan test
```

## Deployment

It runs on Railway from the `Dockerfile` (PHP 8.2 + Apache) with a Railway MySQL database. On every start, `docker/start.sh` runs the migrations and seeders. Both are safe to repeat. Environment variables are listed in `.env.example`.

## Architecture

One Laravel app with server-rendered Blade pages. There's no API, no SPA and no external services.

```
Browser -> web (session, CSRF) -> SetLocale -> auth -> role:<roles> -> Controller -> Blade
                                                                          |
                                         DuplicatePatientChecker, TriageScorer, AuditLog
                                                                          |
                                                                        MySQL
```

The two rules that matter most (duplicate detection and triage scoring) live in small service classes with their own tests.

## Data model

- `users`: name, email, password, `role` (front_desk / nurse / quality_manager)
- `patients`: MRN, name, date of birth, gender, phone (stored as 03XXXXXXXXX), optional CNIC (unique). Phone and DOB are indexed for the duplicate check.
- `visits`: patient, `arrived_at` (starts the wait clock), status
- `triages`: one per visit. The 12 fields, plus `news2_score`, `risk_level` and `flags`, which are calculated on the server when triage is saved.
- `audit_logs`: who did what to which patient, when, and from which IP. Rows are never updated or deleted.

## Rules and where they come from

- **Triage flags:** NEWS2 (Royal College of Physicians, 2017). High risk is a total of 7+. Medium is 5–6, or any single vital scoring 3. There's also a hypertensive crisis flag (BP 180+/120+, ACC/AHA 2017). Impossible values such as pulse 900 or SpO2 140 are rejected.
- **Duplicates:** the same CNIC is blocked. The same phone or date of birth with a name that's at least 80% similar shows a warning. Staff can override it, and the override is logged. A shared phone on its own isn't treated as a duplicate, because families often share one number.

## Known limitations

- NEWS2 is for adults only (the form warns for under-16s). The thresholds haven't been reviewed by a clinician.
- No MFA, no password reset and no admin screen for users. Accounts are seeded.
- No patient edit or merge. Duplicate matching uses string similarity only, and names must be typed in English letters.
- The audit log can't be changed through the app, but it isn't protected at database level.
- There's no retention policy, backups or encryption-at-rest setup yet.
- The dashboard calculates in PHP, which is fine for one hospital but not for ten.
- The Urdu translations need review by a native speaker. The dashboard and audit log screens are English only.

## Time log

See [TIMELOG.md](TIMELOG.md).
