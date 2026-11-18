# CareFlow HMS — OPD-to-Quality thin slice

> ⚠️ **NOT FOR CLINICAL USE.** Demo system with **synthetic data only**. It has not been clinically validated, security-tested or certified, and must never hold real patient information.

A patient arrives at the OPD, is **registered** (with duplicate detection), a **visit** is opened, a nurse records **triage** (vitals are scored and risky readings flagged automatically), and the activity appears on a **quality dashboard** for management. English / **Urdu** toggle on key screens.

**Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Blade · Tailwind CSS · PHPUnit

| | |
|---|---|
| Live app | **https://careflow-hms-production.up.railway.app** |
| Repository | https://github.com/HadiRajani/careflow-hms |
| Demo password (all accounts) | `CareFlow2026!` (synthetic demo accounts only) |
| Front desk | `frontdesk@careflow.test` |
| Nurse | `nurse@careflow.test` |
| Quality manager | `quality@careflow.test` |

---

## 1. Run it locally

Requirements: PHP 8.2+, Composer, Node 20+, MySQL 8 (or MariaDB).

```bash
git clone <repo-url> careflow-hms && cd careflow-hms
composer install
npm install && npm run build
cp .env.example .env            # then set DB_* and DEMO_PASSWORD
php artisan key:generate
# create the database first: CREATE DATABASE careflow_hms CHARACTER SET utf8mb4;
php artisan migrate --seed      # tables + 3 demo users + ~600 synthetic visits (30 days)
php artisan serve               # http://127.0.0.1:8000
```

Run the tests (uses an in-memory SQLite database, never touches MySQL):

```bash
php artisan test
```

Rebuild the demo data at any time: `php artisan migrate:fresh --seed`

## 2. Deploy (Railway)

The repo includes a `Dockerfile` (PHP 8.2 + Apache, assets built with Node) and `docker/start.sh`, which on every start runs migrations and the (repeat-safe) seeders.

1. Railway → **New project → Deploy from GitHub repo** (it detects the Dockerfile).
2. Add a **MySQL** service to the project.
3. In the app service → **Variables**, set:
   ```
   APP_NAME="CareFlow HMS"   APP_ENV=production   APP_DEBUG=false
   APP_KEY=<output of: php artisan key:generate --show>
   APP_URL=https://<your-app>.up.railway.app
   APP_TIMEZONE=Asia/Karachi   LOG_CHANNEL=stderr   SESSION_ENCRYPT=true
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}  DB_PORT=${{MySQL.MYSQLPORT}}  DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}  DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   DEMO_PASSWORD=<choose one>
   ```
4. **Settings → Networking → Generate domain.**

> **Deployment status:** live on Railway (PHP 8.2 + Apache container, Railway MySQL). `railway.json` forces the Dockerfile builder.
>
> **Issues hit on first deploy (and fixes):**
> - App connected to `127.0.0.1` → Railway had applied variables imported from `.env.example`, not the database references; fixed in Railway's variable settings. `docker/start.sh` now logs the DB host it uses and retries the connection for ~60 s.
> - `DEMO_PASSWORD` not found → seeder reads it via `config()` (`env()` returns null after `config:cache`).
> - Apache `More than one MPM loaded` → image keeps only `mpm_prefork` (required by mod_php).

## 3. Architecture

```
Browser (Blade + Tailwind, no SPA)
   │  HTTPS, session cookie, CSRF token
   ▼
Laravel 12 (one app)
   ├─ middleware:  web (session, CSRF) → SetLocale (en/ur) → auth → role:<roles>
   ├─ controllers: Patient · Visit · Triage · Dashboard · AuditLog · Language
   ├─ services:    DuplicatePatientChecker (duplicate rule) · TriageScorer (NEWS2 rule)
   └─ AuditLog::record(...) on every patient view / change
   ▼
MySQL 8  (users · patients · visits · triages · audit_logs)
```

A deliberately simple **monolith**: server-rendered pages, no API, no queue, no external services. Business rules live in two small, unit-tested service classes so they can later be reused by other channels (SMS/USSD, API).

## 4. Data model

| Table | Key columns | Notes |
|---|---|---|
| `users` | name, email, password (bcrypt), **role** | role = `front_desk` \| `nurse` \| `quality_manager` (plain text; not mass-assignable) |
| `patients` | mrn (CF-000123), full_name, date_of_birth, gender, phone, cnic | phone normalised `03XXXXXXXXX`; **indexes** on phone & DOB for the duplicate check; **cnic unique** |
| `visits` | patient_id, **arrived_at**, status (waiting/triaged), created_by | arrived_at starts the wait clock |
| `triages` | visit_id (unique), 12 fields, news2_score, risk_level, flags (JSON), recorded_by, recorded_at | score/flags computed on the server only |
| `audit_logs` | user_id, action, patient_id, subject, meta, ip_address, created_at | append-only (no `updated_at`; model blocks update/delete) |

**The 12 triage fields:** BP (systolic/diastolic), pulse, temperature, SpO2, respiratory rate, consciousness (AVPU), chief complaint, pain score, allergies, fall risk, recorded by, recorded at. The nurse **types 7**; 3 are **one-tap with safe defaults** (Alert, NKDA, low fall risk); the last 2 are **automatic**.

## 5. Clinical rules (and where they come from)

**Triage flags — NEWS2**, Royal College of Physicians (London), 2017. Each vital scores 0–3; the total gives risk:

| Risk | Rule (RCP clinical-response thresholds) |
|---|---|
| High | total ≥ 7 |
| Medium | total 5–6, **or** any single parameter scoring 3 |
| Low | otherwise |

Extra flag outside NEWS2: **hypertensive crisis** (systolic ≥ 180 or diastolic ≥ 120, 2017 ACC/AHA guideline) → at least medium risk. Code: `app/Services/TriageScorer.php`.

**Validation vs flagging.** Physiologically *impossible* values are **rejected** on the server (pulse 20–250, SpO2 50–100 %, temp 30–45 °C, RR 4–60, systolic 50–260, diastolic 20–160 and below systolic). *Abnormal but possible* values are **accepted and flagged**.

**Duplicate detection** (`app/Services/DuplicatePatientChecker.php`): same CNIC → **blocked**. Same phone **or** same DOB **and** name ≥ 80 % similar (word order ignored) → **warning**; staff may override, and the override is audited. Same phone alone is *not* a duplicate — families often share one phone. Threshold chosen by measuring real name pairs (Muhammad/Mohammad 92 %, Ayesha/Aisha 86 %, family members 57–67 %).

## 6. Security & privacy built in

- Login only (Breeze, trimmed): **no public registration**, no self-service account deletion; login rate-limited (5 attempts).
- **Role checks on the server** (`EnsureRole` middleware → 403), proven by tests that call every URL as every role.
- **Audit trail** of patient searches, views, registrations (incl. duplicate overrides), visits and triage.
- Least privilege: the quality manager sees aggregates and the audit log, **not** individual records; staff see no patient list until they search.
- **No patient data leaves the server**: no third-party AI, speech or analytics; dashboard insights are rule-based. (Only Google Fonts are loaded — fonts, no data.)
- Secrets only in environment variables (`.env` is git-ignored); sessions encrypted; HTTPS forced in production; CSRF on all forms.

## 7. Known limitations — not production-ready

- **Clinical:** NEWS2 is validated for adults only (a warning shows for under-16s); no supplemental-oxygen or SpO2 scale-2 handling; not reviewed by a clinician.
- **Duplicate detection:** string similarity only (no phonetic matching); names must be typed in English letters; no merge or edit of patient records.
- **Security:** no MFA, no password reset, no admin screen for accounts (seeded only); audit log is tamper-resistant in the app but not at database level; no penetration test; `trustProxies('*')` assumes the app is only reachable through the platform's proxy.
- **Privacy/compliance:** no data-retention policy, consent capture, encryption-at-rest configuration or backup/restore procedure.
- **Scale:** dashboard calculates in PHP over the period's visits (fine for one hospital); single hospital, no `hospital_id`.
- **Urdu:** translations are AI-drafted and need review by a native speaker with clinical vocabulary; dashboard and audit screens are English-only.
- **Ops:** no monitoring, alerting or CI pipeline; Docker image not built locally.

## 8. Project documents

- [DECISIONS.md](DECISIONS.md) — stakeholder conflicts, what was cut, top 5 risks
- [AI_LOG.md](AI_LOG.md) — key prompts, where the AI was wrong, what I decided
- [ROADMAP.md](ROADMAP.md) — 90 days to a pilot, then 10 hospitals + community care

## 9. Time log

| Session | Date | Start – end (PKT, approx.) | Work |
|---|---|---|---|
| 1 | 17 Nov 2026 | 19:55 – 22:50 | Requirements analysis, setup (MySQL + Composer issues ≈ 30 min), all blocks, tests, docs |
