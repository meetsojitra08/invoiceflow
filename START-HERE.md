# START HERE: Build InvoiceFlow in One Day with AI

You install a few tools and paste one prompt. The AI builds the MVP. You check the result at the end of each phase.

## Realistic plan (about 10 hours of agent time; depends on speed and errors)

| Phase | Tickets | What you get | Target time |
|-------|---------|--------------|-------------|
| 1 Foundation | IF-1, 2, 3 | Running app, auth, workspaces, tenancy, clients, products | 2 h |
| 2 Invoicing | IF-4, 5, 6, 7 | Invoice builder, roles, team, PDF, email, public page | 3 h |
| 3 Money | IF-8, 9, 10 | Payments, Stripe, overdue + reminders | 2 h |
| 4 Insight | IF-11, 12, 13 | Dashboard, reports, REST API, AI invoice draft | 2.5 h |
| 5 Ship | IF-14, 15 | Demo data, CI, deploy | 1 h |

The scope is cut to what makes a solid, job-ready project in a day. Everything else is listed under "Later" in `jira.md`.

## Step 0: Install on your computer (about 30 minutes)

| Tool | Check |
|------|-------|
| PHP 8.3+ and Composer | `php -v`, `composer -V` |
| Node 22 LTS and npm | `node -v` |
| PostgreSQL 16+ (remember the `postgres` password) | `psql --version` |
| Git | `git --version` |
| Laravel installer | `composer global require laravel/installer` then `laravel --version` |

No Redis needed. Windows/Mac: Laravel Herd gives you PHP, Composer, and Node in one install.

## Step 1: Open the project folder in Antigravity
1. Extract the zip into your project folder (`AGENTS.md`, `START-HERE.md`, `jira.md`, `ai-context/`, `docs/`, `feature-specs/` at the root)
2. **File, Open Folder**, select it
3. Let the agent run terminal commands without asking each time (keep approval for deleting files)

## Step 2: Paste this prompt (everything in the box)

```
AUTOPILOT. Read AGENTS.md, everything in ai-context/, and jira.md. Then build the whole MVP, tickets
IF-1 to IF-15 in order, following the Autopilot loop in AGENTS.md. Read each ticket's spec in
feature-specs/ only when you reach it. Do not wait for me between tickets; stop only for the
conditions in AGENTS.md. Post a Phase Report at the end of each phase and keep going to the next
phase unless something blocks you.

IF-1 notes: this folder is NOT empty. Scaffold the Laravel 13 React-starter-kit project (Pest,
PostgreSQL) in a temp subfolder, merge it into the root, keep my files, merge .gitignore, git init.
Create databases invoiceflow and invoiceflow_test. Set QUEUE_CONNECTION, CACHE_STORE, and
SESSION_DRIVER to database. If psql/composer/node is missing, tell me exactly what to install.
No real keys? Use fakes and continue.
```

## Step 3: If the chat runs out of context or stops
Open a NEW chat and paste:

```
AUTOPILOT. Read AGENTS.md, ai-context/, and jira.md. Open ai-context/progress-tracker.md, find the
first unfinished ticket, and continue the build from there using the Autopilot loop. Same rules.
```

## Step 4: If something is broken

```
Fix this: <paste error or describe>. Re-read AGENTS.md and the related spec. Find the root cause,
fix it, add a test that covers it, run the quality gate, commit, update the tracker.
```

## Keys you may need to add yourself (only in `.env`, never in chat)
- **Stripe test keys** (IF-9): create a free Stripe account in test mode. Without keys the agent uses fakes
- **AI API key** (IF-13): from your AI provider console. Without a key the feature is built against a mock
- **Server access** (IF-15): only if you want it deployed; otherwise the agent writes the deploy steps

## Final check (when the agent reports done)
- [ ] `composer run dev` starts; open `http://localhost:8000`
- [ ] Log in with the demo account from the report; click through: client, product, invoice, send, pay
- [ ] `./vendor/bin/pest` passes and `npm run build` works
- [ ] `git log` shows one commit per ticket
