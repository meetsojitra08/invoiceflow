# Progress Tracker

> The agent updates this after every ticket. It is the memory between chats.

## Status
- **Current phase:** 3
- **Current ticket:** IF-9
- **Last completed:** IF-8
- **Last updated:** 2026-11-01

## Tickets (`[ ]` todo, `[~]` in progress, `[x]` done)

### Phase 1: Foundation
- [x] IF-1 Scaffold
- [x] IF-2 Auth, workspaces, tenancy, app shell
- [x] IF-3 Clients and Products

### Phase 2: Invoicing
- [x] IF-4 Invoice domain
- [x] IF-5 Invoice UI
- [x] IF-6 Roles, policies, team
- [x] IF-7 PDF, email, public page

### Phase 3: Money
- [x] IF-8 Payments
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
- 2026-11-01: IF-8 completed. 123 tests passing. Quality gate green. Implemented Payment domain and UI: Payment model with integer minor units and CHECK constraint, PaymentMethod enum, PaymentService::apply() with row locking transaction, balance validation and overpayment prevention, PaymentRecorded and InvoicePaid event dispatching, record payment modal with balance progress bar and payment history table on invoice show page, and Payments list page with KPI cards, method filtering, date filtering, and search.
- 2026-11-01: IF-7 completed. Phase 2 Invoicing complete. 111 tests passing. Quality gate green. Implemented DomPDF template with DejaVu Sans UTF-8 support and watermark status stamps, InvoicePdfService (tenant-aware rendering, streaming, and attachment downloading), queued SendInvoiceEmail job with exponential backoff, Markdown InvoiceMail with dynamic public URL and PDF attachment, send/resend actions, and public guest PDF download endpoint `/i/{token}/pdf`.
- 2026-11-01: IF-6 completed. 103 tests passing. Quality gate green. Implemented Roles, policies, team page, and invitations: role permission matrix across Owner/Accountant/Staff, CompanyPolicy with last-owner protection, CompanyInvitation with 48h expiration and token-based accept flow, Fortify CreateNewUser integration for invitations (joins existing workspace without creating duplicate company), Team UI with role dropdowns, member removal confirm dialog, and copyable invite links.
- 2026-11-01: IF-5 completed. 92 tests passing. Quality gate green. Implemented Invoice UI: live calculation endpoint with validation, full draft builder with product snapshot auto-fill and debounced calculations, invoices list with status filter pills and search, invoice detail view with state action transitions (send, void, duplicate), and responsive public guest invoice view.
- 2026-11-01: IF-4 completed. 84 tests passing. Quality gate green. Implemented Invoice domain: migrations, Invoice & InvoiceItem models with item snapshots, InvoiceStatus enum with lifecycle state transitions, InvoiceCalculator with half-up rounding, InvoiceNumberGenerator with row locking, and InvoiceService (draft/update/send/void/duplicate).
- 2026-11-01: IF-3 completed. Phase 1 Foundation complete. 63 tests passing. Quality gate green. Implemented tenant-scoped Clients and Products with soft deletes, ILIKE search, pagination, validation, minor unit money inputs, policies, and React pages.
- 2026-11-01: IF-2 completed. 47 tests passing. Quality gate green. Implemented multi-tenancy (TenantManager, BelongsToCompany), company workspaces with owner membership on registration, company switcher, shared Inertia props, money utilities, shared UI components, and public layout.
- 2026-11-01: IF-1 completed. 40 tests passing. Quality gate green. Scaffolded Laravel 13 React starter kit with PostgreSQL, Pest, Sanctum, DomPDF, Stripe, Recharts.
