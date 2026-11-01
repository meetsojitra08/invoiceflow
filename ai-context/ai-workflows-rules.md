# AI Workflow Rules (Autopilot)

Full loop and stop conditions are in `../AGENTS.md`. This file lists the working rules.

## Working style
- Autonomous: do not ask for approval between tickets; do not explain concepts; report briefly
- One ticket at a time, in order from `jira.md`; nothing outside the ticket; "Later" items are off limits
- Prefer Laravel and starter-kit defaults over custom code
- Small, focused commits, one per ticket

## Coding rules
- Follow `code-standards.md` and `architecture-context.md`
- Tenant models use `BelongsToCompany`; money is integer minor units; business logic in `app/Services`
- Authorization via Policies on every action; validation via Form Requests
- React/TypeScript strict: no `any`, no business or money rules in components, one `formatMoney` helper
- PostgreSQL only: `jsonb`, partial unique indexes, `ILIKE`, `date_trunc`, `lockForUpdate()`

## Security rules
- No secrets in code; `.env.example` keys stay empty
- Never trust AI/LLM output: validate before use; AI creates drafts only
- Verify webhook signatures; store event IDs for idempotency
- Never use `dangerouslySetInnerHTML` with user or AI content
- Inertia props must not expose other tenants' data or internal columns
- Set `$fillable`; never `$guarded = []`; never disable CSRF, policies, or global scopes to "make it work"

## Done means
- [ ] Acceptance criteria in `jira.md` met
- [ ] Required tests written and passing (see `../AGENTS.md` "Testing")
- [ ] Quality gate green: pint, pest, `npm run types`, `npm run build`
- [ ] No N+1 on list pages; migrations reversible with indexes
- [ ] Tracker ticked, commit made

## Anti-patterns
Fat controllers; logic in React or Blade; float money; swallowed exceptions; hard-coded secrets or tax rates; fetching Inertia data again with `useEffect`; duplicating server rules in TypeScript; rewriting working code outside the ticket.
