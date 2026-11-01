# Tickets: InvoiceFlow One-Day MVP (Project key `IF`)

Build in order. Each ticket: implement, test (see AGENTS.md "Testing"), quality gate, commit on `main`, tick `ai-context/progress-tracker.md`.
Specs live in `feature-specs/` (mapping in `feature-specs/README.md`).

## PHASE 1: Foundation

### IF-1 Scaffold
- Laravel 13 + React starter kit + Pest + PostgreSQL (see AGENTS.md for the non-empty folder rule)
- Databases `invoiceflow` and `invoiceflow_test`; `.env` and `.env.example`; queue/cache/session = `database`
- npm scripts `types` (`tsc --noEmit`) exist; `git init` and first commit
- Install: `php artisan install:api`, `barryvdh/laravel-dompdf`, `stripe/stripe-php`, `recharts`
- Done when: `composer run dev` serves the app on PostgreSQL and `pest` runs

### IF-2 Auth, workspaces, tenancy, app shell (specs 01, 02, 13)
- Registration with company name creates user + company + owner membership in one transaction; `users.current_company_id`
- `TenantManager`, `SetCurrentCompany` middleware, `BelongsToCompany` trait (global scope + auto `company_id`, fail closed)
- `HandleInertiaRequests` shared props (user, company, role, flash); layouts (app, auth, public); sidebar nav; toasts; simple company switcher
- `lib/money.ts` (`formatMoney`, `parseMoneyToMinor`) and shared components (MoneyInput, DataTable, EmptyState, StatCard, StatusBadge, ConfirmDialog, PageHeader)
- Tests: registration transaction, tenant isolation helper

### IF-3 Clients and Products (specs 04, 05)
- Tenant-scoped CRUD with soft delete, `ILIKE` search, sorting, pagination (15), React pages with shared components
- Products: price in minor units via MoneyInput, tax rate, active flag
- Factories + seeders; tests: CRUD, validation, isolation

## PHASE 2: Invoicing

### IF-4 Invoice domain (spec 06)
- Migrations, models, factories: `invoices`, `invoice_items` (snapshots), enum `InvoiceStatus` with transition rules
- `InvoiceCalculator`, `InvoiceNumberGenerator` (transaction + `lockForUpdate`), `InvoiceService` (create/update draft, send, void)
- Tests: calculator (many cases), transitions, number uniqueness per company

### IF-5 Invoice UI (spec 06)
- List (status tabs, search, date filter, pagination), builder (create/edit draft, dynamic rows, product autofill), detail page with actions (send, void, duplicate, download, record payment)
- Live totals via debounced `POST /invoices/calculate`; server recalculates on save
- Tests: save draft, blocked edit when not draft, foreign client/product IDs rejected

### IF-6 Roles, policies, team (spec 03)
- `Role` enum, policies for Client, Product, Invoice, Payment; `can` flags in Inertia resources; hide forbidden UI
- Team page: list members, change role, remove, invite via copyable link (no email); last-owner protection
- Tests: role x action dataset, 403 on forbidden requests

### IF-7 PDF, email, public page (spec 07)
- DomPDF Blade template, download route; queued `SendInvoiceEmail` with PDF attached; sending sets status `sent` and `sent_at`
- Signed public page `/i/{token}` (React, public layout) with Download PDF and Pay now (if balance > 0)
- Mailer = `log` in dev. Tests: mail/queue fakes, tampered link rejected, no data leakage

## PHASE 3: Money

### IF-8 Payments (spec 08)
- `Payment` model, `PaymentService::apply()` in a transaction with row lock; partial/full; status updates; events `PaymentRecorded`, `InvoicePaid`
- Record-payment dialog, payment history on invoice, payments list page
- Tests: partial, full, overpayment rejected, wrong status rejected, isolation

### IF-9 Stripe Checkout and webhook (spec 08)
- `StripeService` creates Checkout Session (metadata `invoice_id`, `company_id`); `POST /webhooks/stripe` (CSRF-excluded): verify signature, insert `webhook_events` (unique provider+event id), queue processing, create payment via `PaymentService`
- No keys: build against fakes; keep keys empty in `.env.example`
- Tests: bad signature 400, duplicate event = one payment

### IF-10 Scheduler and reminders (spec 09)
- Commands `invoices:mark-overdue` (daily) and `invoices:send-reminders` (3/7/14 days after due, once each via unique `invoice_reminders`, skip when client opted out or invoice paid/void), registered in `routes/console.php`; loop companies setting tenant context
- Events `InvoiceSent`, `InvoicePaid` with listener that busts dashboard cache; queue worker runs on `database` driver
- Tests with `Carbon::setTestNow`, no duplicate reminders

## PHASE 4: Insight, API, AI

### IF-11 Dashboard and reports (spec 10)
- `ReportService` aggregate SQL (`date_trunc`), cached per company: revenue this month, outstanding, overdue, recent invoices
- Reports page: revenue by month (Recharts bar), status donut, top 10 clients, aging buckets, date range; CSV export (streamed)
- Tests: KPI numbers with fixtures, isolation, role access

### IF-12 REST API v1 (spec 11)
- Sanctum tokens (create/revoke page, abilities from role), `/api/v1` for clients, products, invoices (send, void, pdf), payments; API Resources; throttle 60/min
- Tests: auth, validation shape, isolation, one happy path per group

### IF-13 AI invoice draft (spec 12)
- `AiService` (HTTP client, timeout, retries, logging to `ai_requests`, feature flag `FEATURE_AI`, daily cap), prompt returns strict JSON, validated, client matched not auto-created
- "Create with AI" dialog in the invoice builder loads an unsaved draft labeled "AI draft: review before saving"
- Tests with `Http::fake`: valid, invalid JSON, timeout, flag off

## PHASE 5: Ship

### IF-14 Demo data, performance, CI
- `DemoSeeder`: company, 3 users (owner@demo.com / password, accountant, staff), 10 clients, 15 products, 40 invoices in all statuses, payments
- `Model::preventLazyLoading` in non-production, fix N+1 on lists/dashboard, add indexes from `docs/DATABASE_SCHEMA.md`
- `.github/workflows/ci.yml` (PostgreSQL service: pint, pest; node: types, build)
- README: quick start and demo login

### IF-15 Deploy (only if I give a platform/server; otherwise write steps)
- Follow `docs/DEPLOYMENT.md`: production env, build, migrate, queue worker, cron scheduler, HTTPS
- Done when: live URL works with the demo login (or deploy steps are verified locally)

## Later (do NOT build now)
Recurring invoices, AI reminder writer and monthly summary, Horizon/Redis, queued Excel exports, email invitations with expiry, Row Level Security, Scribe API docs, Razorpay, multi-currency, Vitest/PHPStan/E2E tests, dark mode.
