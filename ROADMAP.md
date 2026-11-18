# Roadmap

## First 90 days: pilot in one hospital

**Month 1: make it safe for real data**
- Get the triage rules signed off by the Medical Director, and add a paediatric score.
- Add user management, MFA, auto-logout, and a pen test.
- Write the data protection basics: retention, consent, backups that have been restore-tested, and encrypted storage.
- Add patient edit and merge, and better duplicate matching.
- Have the Urdu text checked by nursing staff.
- Agree hosting with IT (on-premise or in-country cloud).

**Month 2: run it next to paper**
- Use it alongside paper in one OPD for a few weeks.
- Time real triage against the 60-second target and fix whatever slows nurses down.
- Have the quality manager check the dashboard numbers against a manual sample.

**Month 3: go live in the OPD**
- Retire paper for registration and triage, and keep a paper fallback for downtime.
- Targets: 95% of visits triaged in the system, 80% within 30 minutes, under 2% duplicates, no data incidents.
- Show the board a voice prototype that runs on our own hardware.

## Growing to 10 hospitals and community care

- Add a `hospital_id` to the data, with one patient identity shared across all sites.
- Move from a role column to proper roles and permissions per hospital.
- Move dashboard numbers to SQL summaries refreshed overnight, and load test before each new site opens.
- Add an API (FHIR) so labs, pharmacy and national programmes can connect.
- Add a USSD/SMS menu for community health workers on basic phones, with Urdu menus. It reuses the same duplicate check.
- Build preventive care lists (overdue BP and diabetes screening) as outreach lists for health workers.
- Use AI only with models we host ourselves, with no patient identifiers in prompts and a person reviewing the output.
