# InvoiceFlow

Multi-tenant invoicing and client management SaaS built with **Laravel 13, PostgreSQL, and React + Tailwind CSS** (Inertia), with an AI invoice-draft feature.

**Live demo:** `https://your-demo-url.com` | **Demo login:** `owner@demo.com` / `password`

## Features
- Company workspaces (multi-tenant, `company_id` scoping) with roles: Owner, Accountant, Staff
- Clients, products with tax rates, invoices (draft, sent, partially paid, paid, overdue, void)
- Live-total invoice builder, PDF, email, public invoice page
- Manual and Stripe payments with idempotent webhooks
- Overdue marking and automatic reminders (scheduler)
- Dashboard, reports, CSV export
- Token-authenticated REST API (`/api/v1`)
- AI: create an invoice draft from a text prompt

## Tech Stack
| Layer | Tools |
|-------|-------|
| Backend | Laravel 13, PHP 8.3+ |
| Database | PostgreSQL 16+ |
| Frontend | React 19 + TypeScript, Inertia.js, Tailwind CSS 4, shadcn/ui, Recharts |
| Auth | Starter-kit auth (Fortify), Sanctum (API) |
| Queue / cache | Laravel `database` drivers |
| PDF / Payments / AI | DomPDF, Stripe, Claude API |
| Tests / CI | Pest, GitHub Actions |

## Quick Start
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
createdb invoiceflow            # set DB_* in .env (pgsql)
php artisan migrate --seed
composer run dev                # app + queue worker + Vite
php artisan schedule:work       # scheduler (another terminal)
```

## Project files
| File | Purpose |
|------|---------|
| [START-HERE.md](START-HERE.md) | How to build it with the AI agent |
| [AGENTS.md](AGENTS.md) | Agent rules |
| [jira.md](jira.md) | Ticket list (IF-1 to IF-15) |
| [prompt.md](prompt.md) | Prompts to paste |
| [ai-context/](ai-context/project-overview.md) | Overview, architecture, standards, UI, tracker |
| [feature-specs/](feature-specs/README.md) | Behavior per feature |
| [docs/](docs/SETUP.md) | Setup, schema, API, testing, deployment |

## License
MIT
