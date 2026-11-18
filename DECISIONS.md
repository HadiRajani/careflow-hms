# Decisions

## Stakeholder conflicts

**Speed vs the 12 fields (Medical Director vs Quality Manager).** I didn't think these really had to conflict. The triage form saves all 12 fields, but the nurse only types seven of them: the six vitals and the complaint. Consciousness, allergies and fall risk default to the common answer (Alert, NKDA, low), so they take one tap only when something is different. "Recorded by" and the time are filled in automatically. Triage is one click to open the patient and one click to save. The server rejects incomplete records, so nothing can fail an audit for missing fields.

**AI and voice vs no data leaving the hospital (CEO vs Head of IT).** I sided with IT. A data leak can't be undone, but a board demo can wait. The easy way to add voice is the browser's speech API, but in Chrome that sends the audio to Google, so I left it out. The dashboard "insights" are plain rules over our own data, not an AI model. For the board, the roadmap has speech-to-text running on our own servers.

**Basic phones and Urdu (Community Health Lead).** For the alternative input I added an English/Urdu switch. It helps the front desk and nurses, who are the people using this part of the system today. I looked at a USSD menu first, but the patient is already standing at the OPD here, and entering vitals on a keypad isn't safe. USSD makes more sense for the community programme, so it's on the roadmap. Numbers always stay in Western digits, and I forced ranges to display left-to-right, because in Urdu mode "51–90" was showing up reversed.

**Traceability (Head of IT).** Every patient search, view, registration, visit and triage goes into the audit log, and the quality manager can see it.

## What I cut

- Voice, the AI summary and USSD, for the reasons above.
- The optional stretch item. I wanted the core working properly first.
- Admin screens, password reset, MFA, and editing or merging patients.
- A roles/permissions table. With three fixed roles, a single column is easier to read and change.

## Top 5 risks before go-live

1. **Clinical:** NEWS2 is adult-only and my thresholds haven't been checked by a clinician. A wrong score could make a sick patient look fine. Needs clinical sign-off, a paediatric score, and training that the score supports judgement rather than replacing it.
2. **Security:** busy OPD computers get left logged in and passwords get shared. Needs MFA or SSO, auto-logout, proper account provisioning and a pen test.
3. **Compliance:** there's no retention policy, consent process, backup plan or encryption at rest yet. Needs a data protection review before any real patient data goes in.
4. **Data quality:** duplicate detection misses phonetic spellings, and without a merge feature a patient's history can end up split across two records. Needs phonetic matching and a merge workflow.
5. **Scale:** one server, and the dashboard maths runs in PHP. Fine for one OPD, too slow for ten hospitals. Needs SQL summaries, a `hospital_id` on the data, and load testing.
