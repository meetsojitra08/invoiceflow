# Spec: Multi-Tenancy

**Ticket:** IF-2

> **MVP scope:** Everything except the PostgreSQL Row Level Security stretch. Test plan: Pest only (skip Vitest).

## 1. Purpose
Guarantee that each company only ever sees and changes its own data.

## 2. User Stories
- As a company Owner, my data is invisible to other companies.
- As a developer, I get tenant filtering automatically without remembering `where company_id`.

## 3. Functional Requirements
- FR-1: `BelongsToCompany` trait applies a global scope `company_id = current tenant`
- FR-2: On `creating`, `company_id` is set automatically from the current tenant
- FR-3: `TenantManager` stores the current company id; `SetCurrentCompany` middleware sets it from `auth()->user()->current_company_id`
- FR-4: Jobs, commands, and listeners call `TenantManager::set($companyId)` explicitly
- FR-5: Route model binding resolves through the scope, so other tenants' IDs return 404
- FR-6: If no tenant is set in a web request, tenant models throw an exception (fail closed)

## 4. Business Rules
- Tenant models: Client, Product, Invoice, Payment, RecurringInvoice, AiRequest
- Child tables (InvoiceItem) are reached via their tenant parent
- `withoutGlobalScopes()` is allowed only in documented admin/maintenance code
- Unique rules and `exists` rules must include `company_id`
- Inertia props must never include other tenants' data or internal columns (props are visible in page source)
- Cache keys include `company_id`

## 5. Data Model
`company_id` (indexed, foreign key) on every tenant table.

## 6. Permissions
Not role-based. Applies to everyone.

## 7. UI / UX
No dedicated UI. Company name visible in the top bar.

## 8. Edge Cases and Failure Modes
- Queue worker has no authenticated user, so tenant must be passed in the job
- Scheduler command loops all companies, setting and clearing tenant each iteration
- User submits a foreign `client_id` in a form, so validation must reject it
- Seeders and tests need an explicit tenant context

## 9. Acceptance Criteria
- [ ] A user from company A gets 404 on company B's records (web)
- [ ] `company_id` auto-filled on create
- [ ] Missing tenant context fails loudly, never returns all rows
- [ ] Jobs process the correct tenant's data

## 10. Test Plan
Create a reusable Pest helper `actingAsTenant()`. For each tenant model test: list, show, update, delete across tenants; foreign-ID validation; job runs under correct tenant.

## 11. Out of Scope
Database-per-tenant, subdomain routing, super-admin panel.

