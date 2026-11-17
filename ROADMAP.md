# ROADMAP — from thin slice to pilot, then to a network

## 90 days to a pilot in one real hospital

**Days 1–30 · Make it safe to touch real data**
- Clinical sign-off of triage rules with the Medical Director; add paediatric score (PEWS) and oxygen/SpO2 scale 2.
- Security baseline: admin user management, MFA/SSO, idle auto-logout, password policy, external pen test.
- Compliance: DPIA, retention and consent policy, encrypted backups with a tested restore, audit log to write-once storage.
- Patient edit/merge with audit; phonetic duplicate matching; Urdu translations reviewed by native clinical staff.
- Hosting decision with Head of IT (on-premise or in-country cloud); CI pipeline running the test suite on every change.

**Days 31–60 · Shadow run in one OPD**
- Run alongside paper for 2–4 weeks; measure clicks and seconds per triage against the 60-second target.
- Weekly feedback sessions with nurses and front desk; fix friction first.
- Quality Manager validates dashboard numbers against a manual sample.

**Days 61–90 · Go live in the OPD**
- Paper retired for registration/triage; on-call support; incident and downtime procedures (paper fallback).
- Success measures: ≥ 95 % visits triaged in the system, ≥ 80 % within 30 min, duplicate rate < 2 %, zero data incidents.
- Board demo: real (de-identified) dashboard trends and **on-premise voice** chief-complaint prototype (Whisper on hospital hardware, so no audio leaves).

## Growing to 10 hospitals + community and preventive care

- **Multi-site data model:** add `hospital_id` to users and clinical tables; one patient identity across sites (master patient index on CNIC + phone + phonetic name).
- **Access control:** move from a role column to **roles and permissions tables** (RBAC), scoped per hospital; central admin console.
- **Performance:** dashboard from SQL aggregates and nightly summary tables; read replicas; queues for reports and notifications; load testing before each new site.
- **Interoperability:** an API layer and HL7 FHIR resources (Patient, Encounter, Observation) for labs, pharmacy and national programmes.
- **Community channel (Community Health Lead):** **USSD/SMS menu** through a telecom aggregator so health workers on basic phones can pre-register and refer patients; it reuses the same `DuplicatePatientChecker`. **Urdu text in menus**, and SMS reminders.
- **Preventive care:** overdue BP and diabetes screening lists generated from visit history; outreach lists for health workers; follow-up for patients returning within 7 days.
- **AI, with guardrails:** only models hosted inside our infrastructure; human review of any AI-written summary; no patient identifiers in prompts; evaluate before use.
- **Operations:** monitoring and alerting, uptime targets per site, a support team and a release process with staged rollouts.
