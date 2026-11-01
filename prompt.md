# Prompts (copy and paste)

Per-ticket prompts are not needed: the agent reads `jira.md` and the specs itself. Use only these.

## 1. Build everything (first run)
```
AUTOPILOT. Read AGENTS.md, everything in ai-context/, and jira.md. Then build the whole MVP, tickets
IF-1 to IF-15 in order, following the Autopilot loop in AGENTS.md. Read each ticket's spec in
feature-specs/ only when you reach it. Do not wait for me between tickets; stop only for the
conditions in AGENTS.md. Post a Phase Report at the end of each phase and keep going unless blocked.

IF-1 notes: this folder is NOT empty. Scaffold the Laravel 13 React-starter-kit project (Pest,
PostgreSQL) in a temp subfolder, merge it into the root, keep my files, merge .gitignore, git init.
Create databases invoiceflow and invoiceflow_test. Set QUEUE_CONNECTION, CACHE_STORE, and
SESSION_DRIVER to database. No real keys? Use fakes and continue.
```

## 2. Continue (new chat, or after a stop)
```
AUTOPILOT. Read AGENTS.md, ai-context/, and jira.md. Open ai-context/progress-tracker.md, find the
first unfinished ticket, and continue the build from there using the Autopilot loop. Same rules.
```

## 3. Fix a problem
```
Fix this: <paste error or describe>. Re-read AGENTS.md and the related spec. Find the root cause,
fix it, add a test that covers it, run the quality gate, commit, update the tracker.
```

## 4. Quick review (any time)
```
Review the current code against ai-context/code-standards.md and ai-context/architecture-context.md.
Find and FIX: tenant leaks (missing BelongsToCompany or company_id checks), missing authorization,
float money, N+1 queries, missing validation, business logic in controllers or React components.
Run the quality gate, commit, and list what you changed in 5 lines.
```

## 5. Add a "Later" feature
```
Add this feature from the "Later" list in jira.md: <name>. Create a short spec if none exists,
implement it with tests, run the quality gate, commit, update the tracker. Nothing else.
```

## 6. Deploy
```
Deploy to <platform/server>. Follow docs/DEPLOYMENT.md. I will put the secrets in the environment
myself; tell me which variables to set. Verify migrate, build, queue worker, scheduler, HTTPS, and
the Stripe webhook URL, then report the live URL and demo login.
```
