# AI log

I used Claude Code for most of the typing. I went through every part with it, asked it to explain anything I didn't follow, and pushed back when something didn't make sense.

## Prompts that mattered most

1. The kick-off: build the CareFlow MVP in Laravel, MySQL, Blade and Tailwind, explain each stage before building it, and be honest about anything unfinished. It pointed out that my prompt had missed some required parts of the brief (the alternative input, the 500-visit seeder, deployment and the plain-language insight).
2. "If triage can't be done over USSD, what's the use of it? The patient is at the hospital anyway." This changed the design (see below).
3. "I don't understand this enum role thing, keep the code simple so I can explain and change it." Roles became a plain text column with simple `if` checks.
4. "How do the guest and auth middleware work? I don't see them in the code." This led to finding a real bug.
5. "Is this in the task, or can it go to the roadmap?" I used this to keep voice and USSD out of the build.

## Where the AI got it wrong

- **It recommended a USSD menu** as the alternative input. I questioned it because the patient is already at the OPD and vitals can't safely go through a keypad. We switched to the Urdu toggle.
- **The login redirect bug.** When I asked how the `guest` middleware works, we read Laravel's source. By default, a logged-in user who opens `/login` gets sent to the route called `dashboard`. That page is for the quality manager only, so a nurse got a 403 error. I fixed it to send each role to its own page and added a test.
- **Unsafe defaults from the starter kit.** Breeze comes with public sign-up and "delete my account". On a hospital system, anyone could have created an account. I removed both.
- **Urdu numbers displayed backwards.** All the tests passed, but on the screenshots the normal ranges in Urdu mode were reversed ("90-51" instead of "51-90"). On a triage form that's a real risk. It's fixed now.
- **Smaller bugs found along the way:** the app was running in UTC instead of Pakistan time; visits never triaged on earlier days kept blocking new visits; Urdu-script names silently broke duplicate matching; and reading the demo password with `env()` would have crashed the production deploy.

## What I decided myself

- The Urdu toggle instead of USSD, and no outside AI or speech services.
- Simple code over clever code: plain role strings and `if` statements, so I can change things live.
- Warn rather than block when names are similar (two sisters can score 90%), but block outright on the same CNIC.
- The Urdu wording and the NEWS2 thresholds still need checking by people qualified to judge them.
