# Progress Tracker

> The agent updates this after every ticket. It is the memory between chats.

## Status
- **Current phase:** 1
- **Current ticket:** IF-2
- **Last completed:** IF-1
- **Last updated:** 2026-11-01

## Tickets (`[ ]` todo, `[~]` in progress, `[x]` done)

### Phase 1: Foundation
- [x] IF-1 Scaffold
- [ ] IF-2 Auth, workspaces, tenancy, app shell
- [ ] IF-3 Clients and Products

### Phase 2: Invoicing
- [ ] IF-4 Invoice domain
- [ ] IF-5 Invoice UI
- [ ] IF-6 Roles, policies, team
- [ ] IF-7 PDF, email, public page

### Phase 3: Money
- [ ] IF-8 Payments
- [ ] IF-9 Stripe Checkout and webhook
- [ ] IF-10 Scheduler and reminders

### Phase 4: Insight, API, AI
- [ ] IF-11 Dashboard and reports
- [ ] IF-12 REST API v1
- [ ] IF-13 AI invoice draft

### Phase 5: Ship
- [ ] IF-14 Demo data, performance, CI
- [ ] IF-15 Deploy

## Decisions
| Decision | Reason |
|----------|--------|
| Single-DB multi-tenancy with `company_id` | Simple, standard |
| Money as integer minor units | No float errors |
| React + Inertia | Required stack; one routing/validation/auth system |
| PostgreSQL only | Required stack |
| `database` queue/cache/session drivers | No Redis setup, faster start |
| Server computes invoice totals | One source of truth |

## Pending manual items (keys, accounts, access)
| Item | Needed for | Status |
|------|------------|--------|
| Stripe test keys in `.env` | IF-9 live test | pending |
| AI API key in `.env` | IF-13 live test | pending |
| Server/platform access | IF-15 | pending |

## Known issues
| Description | Ticket | Status |
|-------------|--------|--------|
| | | |

## Session log
- 2026-11-01: IF-1 completed. 40 tests passing. Quality gate green. Scaffolded Laravel 13 React starter kit with PostgreSQL, Pest, Sanctum, DomPDF, Stripe, Recharts.
