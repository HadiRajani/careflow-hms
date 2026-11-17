# DECISIONS

## Stakeholder conflicts

**Medical Director (≤ 3 clicks / 60 s) vs Quality Manager (12 fields).** Both met. All 12 fields are captured and required, but the nurse only **types 7** (6 vitals + complaint). Consciousness, allergies and fall risk are **one-tap with safe defaults** (Alert, NKDA, low). "Recorded by/at" are **automatic**. Flow: click patient in queue → type → **Save** (2 clicks). The server rejects incomplete records, so none can fail an audit.

**CEO (AI + voice for the board) vs Head of IT (no patient data to outside services).** IT wins on safety: a data leak is irreversible, a demo is not. Browser speech recognition was rejected because **Chrome's Web Speech API sends audio to Google**. No LLM either. Dashboard insights are **rule-based** sentences from our own data. For the board: the roadmap proposes **on-premise** speech-to-text (e.g. Whisper on hospital hardware) so voice can come without breaking the rule.

**Community Health Lead (basic phones, Urdu).** I chose the **English/Urdu toggle**: it serves the people in *this* journey (front desk and nurses) today. I first considered a simulated USSD menu, but the patient is physically at the OPD and triage can't safely be done on a keypad, so USSD adds little to this slice. It is the first community item on the roadmap. Clinical numbers stay in Western digits and ranges are forced left-to-right, so a vital sign is never misread.

**Head of IT (traceability).** Every patient search, view, registration (including duplicate overrides), visit and triage is written to an append-only audit log, visible to the quality manager.

## What I cut, and why
- **Voice input, AI summary, USSD**: privacy rule (voice/AI) and scope; on the roadmap.
- **Optional stretch** (preventive-care list): the core came first.
- **Admin screens, password reset, MFA, patient edit/merge**: accounts are seeded; listed as limitations.
- **Roles table / permissions package**: 3 fixed roles → a plain `role` column is simpler and explicit; RBAC when roles multiply.
- **Charts library**: bars are plain HTML/CSS, so there is no extra dependency or external script.

## Top 5 risks before go-live
1. **Clinical safety — triage rule misuse.** NEWS2 is adult-only, needs oxygen/AVPU nuance, and my thresholds are not clinician-reviewed. *Mitigation:* clinical sign-off of rules, paediatric score (PEWS), "supports, never replaces judgement" training, monitor missed deteriorations.
2. **Security/privacy — account compromise.** Shared or weak passwords on busy OPD PCs. *Mitigation:* MFA or SSO, auto-logout on idle, per-user accounts only, admin provisioning with joiner/leaver process, pen test.
3. **Compliance — no data-protection framework.** Retention, consent, breach process and encryption at rest are undefined (Pakistan's draft Personal Data Protection Bill, hospital policy). *Mitigation:* DPIA, retention policy, DB encryption + backups with tested restore, audit log shipped to write-once storage.
4. **Data quality — duplicates and identity.** String similarity misses phonetic variants; there is no merge, so duplicate records split a patient's history. *Mitigation:* phonetic matching, CNIC capture drive, merge workflow with audit, weekly duplicate report.
5. **Performance/scalability — single instance, PHP-side analytics.** Fine for one OPD (~1k visits/month); slow at 10 hospitals. *Mitigation:* SQL aggregates or a nightly summary table, read replica, `hospital_id` tenancy, load test before each new site.
