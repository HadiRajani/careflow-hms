# AI_LOG

Tool: **Claude Code** (Claude Opus) in the terminal, working in the repository. I reviewed, questioned and tested every part; below is where it mattered.

## Most important prompts

1. **Kick-off:** *"Act as a senior Laravel developer and technical mentor. Build the CareFlow HMS assessment… implement a simple, clean and functional MVP using Laravel, MySQL, Blade and Tailwind… Before each major stage, explain what you are building and why… Be honest about incomplete features, test results and deployment status."* The AI then pointed out that my prompt missed required items: the alternative input method, the 500-visit seeder, deployment, the plain-language insight, and the video/time log.
2. **Challenging the alternative-input choice:** *"If triage can't be covered in USSD, what's the use of it? If a person comes to the hospital they can be registered there."* (see mistake 1)
3. **Simplicity over cleverness:** *"I don't understand this enum role thing… keep simplified code across the app so it's easy for me to understand and change at runtime."* This led to the plain `role` column, plain `if` statements and English comments everywhere.
4. **Understanding framework internals:** *"How are the guest and auth middleware working? I don't see any such middleware in the code."* (see mistake 3)
5. **Scope control:** *"Is it mentioned in the task? Or can we save it for the roadmap?"* This kept voice and USSD out of the build, with written reasons.

## Where the AI was wrong or unsafe, and how it was caught

1. **Wrong recommendation (caught by me).** The AI recommended a simulated **USSD menu** as the alternative method. I asked what value it adds when the patient is already at the OPD and triage can't safely be done on a keypad. The AI agreed: USSD serves future community outreach, not this journey. **I changed the decision to the English/Urdu toggle**; USSD moved to the roadmap.
2. **Unsafe default — voice input.** The easy path for "voice" is the browser Web Speech API, but in Chrome it **sends audio to Google**, which breaks the Head of IT's rule. Rejected; on-premise speech-to-text goes on the roadmap.
3. **Framework default bug (found by my question).** Reading Laravel's `RedirectIfAuthenticated` source showed that a logged-in user opening `/login` is sent to the route named `dashboard`. That page is quality-manager only, so **a nurse got a 403**. Fixed with a role-based redirect plus a regression test.
4. **Unsafe scaffold defaults.** Breeze ships **public self-registration**, email reset and self-service account deletion: anyone could create an account, and deleting a nurse would orphan the audit trail. Removed; a test proves `/register` returns 404. `role` kept out of `$fillable` to block privilege escalation.
5. **Clinical-safety display bug (caught by screenshots, not tests).** In Urdu right-to-left mode, normal ranges displayed **reversed** ("51-90" → "90-51", "36.1-38.0" → "38,0-36,1"). All tests were green. Fixed by isolating numbers in a left-to-right `<bdi>`.
6. **Silent failure in duplicate detection.** Name normalisation keeps only a–z, so a name typed in Urdu script became empty and **matching silently stopped**. Names must now be entered in English letters (as on the CNIC), validated and tested. The similarity threshold was measured on real name pairs, not guessed; reversed names ("Bilal Ahmed" / "Ahmed Bilal" = 45 %) were fixed by sorting words.
7. **Logic, time and deploy bugs.** Visits never triaged on earlier days stayed "waiting" forever, which clogged the queue and blocked new visits. The app ran in **UTC** and showed 16:41 for a 21:41 PKT arrival. The seeder called `env()`, which **returns null after `config:cache`** in production and would have crashed the deploy. All fixed; the first two have regression tests.

## What I decided or rewrote myself
- Urdu toggle instead of USSD; voice and AI kept out because of the IT rule.
- Plain-text roles and plain `if`/`else` instead of PHP enums, so I can explain and change it live.
- Warn-not-block for name matches (sisters "Sara/Sana Ahmed" score 90 %), but hard-block the same CNIC.
- **Still to verify by a human:** Urdu wording, by a native speaker with clinical vocabulary; NEWS2 thresholds, by a clinician.
