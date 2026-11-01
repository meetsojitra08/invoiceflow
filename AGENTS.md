# InvoiceFlow: Agent Rules (AUTOPILOT, ONE-DAY MVP)

Build the InvoiceFlow MVP end to end, as fast as possible, with no hand-holding. Do not teach or explain; ship working, tested code.

## Stack (fixed)
Laravel 13 (PHP 8.3+), PostgreSQL 16+, React 19 + TypeScript + Tailwind CSS 4 + shadcn/ui via Inertia.js, Pest. Queue/cache/session drivers = `database` (no Redis needed).

## Source of truth
- `ai-context/` (overview, architecture, code standards, UI, tracker, workflow rules)
- `jira.md` (the ticket list: IF-1 to IF-15)
- `feature-specs/` (behavior per feature; each spec has an "MVP scope" line, follow it)
Read `ai-context/` and `jira.md` once at the start, and a spec only when you start its ticket.

## Autopilot loop (every ticket, in order, no waiting)
1. Take the first unfinished ticket in `ai-context/progress-tracker.md`
2. Read its ticket in `jira.md` and its spec
3. Implement everything: migrations, models, factories, services, controllers, React pages
4. Write the tests listed in "Testing" below (Pest only)
5. Quality gate: `./vendor/bin/pint`, `./vendor/bin/pest`, `npm run types`, `npm run build` (and `npm run lint` if the script exists)
6. Fix failures (max 3 attempts per problem)
7. Commit (`feat(IF-n): ...`) on `main`; no pull requests
8. Tick the ticket in `ai-context/progress-tracker.md`; go to the next ticket immediately

## Speed rules
- Be terse. Status lines only; no tutorials, no long explanations, no extra documents
- Batch work: create many files per step, run commands in one go
- Use the starter kit and shadcn/ui components as they are; do not polish or redesign
- Do not add features, packages, or refactors outside the ticket. Skip everything marked "Later" in `jira.md`
- Never block on a missing key: build against fakes/mocks (`Http::fake`, `Mail::fake`), keep `.env.example` keys empty, and note it in the tracker under "Pending manual"

## Testing (keep it lean, but do these)
- `InvoiceCalculator` unit tests (rounding, tax, discount)
- Tenant isolation for every tenant model (other tenant's ID returns 404)
- Policy checks per role for invoices/payments/team
- Payment service (partial, full, overpayment) and Stripe webhook (bad signature, duplicate event)
- API auth + one happy path per endpoint group
- Skip Vitest, PHPStan, and E2E tests for the MVP

## Stop and ask ONLY when
- A required tool is missing (psql, composer, node) and you cannot continue
- A destructive action is needed (dropping real data, force push)
- A problem persists after 3 different fix attempts
- All tickets are done, or the end of a phase is reached: post the Phase Report and wait

## Hard rules
- Every tenant model uses `BelongsToCompany`; money is integer minor units, never floats
- Business logic in `app/Services`, never in controllers, models, or React components
- Server is the source of truth (totals, permissions, status transitions); UI `can` flags only hide buttons
- Validation via Form Requests; authorization via Policies on every action
- PostgreSQL only; no SQLite; no secrets in code
- Never delete or overwrite `ai-context/`, `docs/`, `feature-specs/`, `jira.md`, `prompt.md`, `README.md`, `START-HERE.md` (only tick boxes in `progress-tracker.md`)
- If the project folder is not empty when scaffolding, scaffold into a temp subfolder, merge into the root, keep my files, merge `.gitignore`

## Phase Report (end of each phase)
Tickets done, test and gate results, commands to run the app + demo login, a short click-through checklist, pending manual items, known issues.
