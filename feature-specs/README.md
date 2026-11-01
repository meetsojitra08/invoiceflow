# Feature Specs

Behavior per feature. Read a spec when you start its ticket. Each spec starts with an **MVP scope** line: build exactly that, skip the rest.

| Spec | Ticket |
|------|--------|
| [01 Authentication and workspaces](01-authentication-and-workspaces.md) | IF-2 |
| [02 Multi-tenancy](02-multi-tenancy.md) | IF-2 |
| [13 App shell and UI](13-app-shell-and-ui.md) | IF-2 |
| [04 Clients](04-clients.md) | IF-3 |
| [05 Products](05-products.md) | IF-3 |
| [06 Invoices](06-invoices.md) | IF-4, IF-5 |
| [03 Roles and team](03-roles-and-team.md) | IF-6 |
| [07 Invoice PDF and email](07-invoice-pdf-and-email.md) | IF-7 |
| [08 Payments](08-payments.md) | IF-8, IF-9 |
| [09 Automation](09-automation.md) | IF-10 |
| [10 Dashboard and reports](10-dashboard-and-reports.md) | IF-11 |
| [11 REST API](11-rest-api.md) | IF-12 |
| [12 AI features](12-ai-features.md) | IF-13 |

Stack: Laravel 13, PostgreSQL, React 19 + TypeScript + Tailwind CSS (Inertia). If a spec mentions Vitest, Horizon, Redis, or Scribe, ignore it for the MVP.
