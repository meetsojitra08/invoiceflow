# Project Overview: InvoiceFlow

Multi-tenant invoicing and client management SaaS. Companies sign up, manage clients and products, create and email invoices, get paid online, and see reports. Built with Laravel 13, PostgreSQL, and React + Tailwind (Inertia).

## Users
| Persona | Needs |
|---------|-------|
| Owner | Everything: team, settings, reports, payments |
| Accountant | Invoices, payments, reports |
| Staff | Clients, draft invoices |
| Client (external) | View and pay an invoice via a public link |
| Developer | Token-authenticated REST API |

## Tech Stack (fixed)
Laravel 13 (PHP 8.3+), PostgreSQL 16+, React 19 + TypeScript + Tailwind CSS 4 + shadcn/ui via Inertia.js, Sanctum, DomPDF, Stripe, Claude API, Pest. Queue/cache/session on the `database` driver.

## MVP Features (build these)
1. Registration creates a company workspace (tenant); company switcher
2. Roles Owner / Accountant / Staff with Policies; team page
3. Clients and products (tax rates)
4. Invoices: draft, sent, partially_paid, paid, overdue, void; live-total builder
5. PDF, email, signed public invoice page
6. Payments: manual/partial and Stripe Checkout with idempotent webhooks
7. Scheduler: overdue marking and reminders
8. Dashboard, reports, CSV export
9. REST API `/api/v1` with Sanctum
10. AI: invoice draft from a text prompt

## Later (out of scope now)
Recurring invoices, AI reminder writer and monthly summary, Horizon/Redis, queued Excel export, email invitations, Row Level Security, Scribe docs, Razorpay, multi-currency, accounting ledger, inventory, mobile app.

## Success Criteria
- A new company can register, add a client, create and email an invoice, and get paid in test mode
- Tenant A can never read or write tenant B's data (tests prove it)
- Duplicate webhooks never create duplicate payments
- Money math is unit-tested; quality gate green; demo data seeded

## Glossary
| Term | Meaning |
|------|---------|
| Tenant / Company | One customer business with an isolated workspace |
| Minor units | Smallest currency unit (paise/cents), stored as integers |
| Snapshot | Copy of product data stored on invoice items |
| Idempotent | Processing the same event twice equals processing it once |

## Related
`architecture-context.md`, `code-standards.md`, `ui-context.md`, `ai-workflows-rules.md`, `progress-tracker.md`, `../jira.md`, `../feature-specs/`
