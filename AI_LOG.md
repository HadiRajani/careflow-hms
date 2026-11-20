# AI log

I used Claude Code for most of the typing. I set the scope and the rules, reviewed each part before moving on, and had it walk me through anything I'd need to defend or change live.

## Prompts that mattered most

1. The kick-off: build the CareFlow MVP in Laravel, MySQL, Blade and Tailwind, explain each stage before building it, stay inside three hours, and be honest about anything unfinished. We checked the prompt against the brief and added the items it didn't name (the alternative input, the 500-visit seeder, deployment and the plain-language insight).
2. "If triage can't be done over USSD, what's the use of it? The patient is already at the hospital." I used this to reject its USSD suggestion (see below).
3. "Keep the code simple: plain strings and if/else, no enums." I set this rule so every line is something I can explain and edit live. Roles became a plain text column with simple checks.
4. "Walk me through exactly how the guest and auth middleware redirect users." Reviewing this flow turned up a real bug.
5. "Is this in the task, or can it go to the roadmap?" I used this to keep voice and USSD out of the build.
6. "Show audit actions as 'Patient Searched', not 'patient.searched'." The audit log has to be readable by a quality manager, not just a developer.

## Where the AI got it wrong

- **It recommended a USSD menu** as the alternative input. I rejected it: the patient is already at the OPD, and vitals can't safely go through a keypad. We switched to the Urdu toggle.
- **The login redirect bug.** Reviewing the `guest` middleware in Laravel's source showed that a logged-in user who opens `/login` gets sent to the route called `dashboard`. That page is for the quality manager only, so a nurse got a 403 error. I had it send each role to its own page and added a test.
- **Unsafe defaults from the starter kit.** Breeze comes with public sign-up and "delete my account". On a hospital system, anyone could have created an account. I removed both.
- **Urdu numbers displayed backwards.** All the tests passed, but on the screenshots the normal ranges in Urdu mode were reversed ("90-51" instead of "51-90"). On a triage form that's a real risk. It's fixed now.
- **Smaller bugs found along the way:** the app was running in UTC instead of Pakistan time; visits never triaged on earlier days kept blocking new visits; Urdu-script names silently broke duplicate matching; and reading the demo password with `env()` would have crashed the production deploy.

## What I decided myself

- The Urdu toggle instead of USSD, and no outside AI or speech services.
- Simple code over clever code: plain role strings and `if` statements, so I can change things live.
- Warn rather than block when names are similar (two sisters can score 90%), but block outright on the same CNIC.
- The Urdu wording and the NEWS2 thresholds still need checking by people qualified to judge them.
