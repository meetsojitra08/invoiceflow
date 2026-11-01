# Spec: Dashboard and Reports

**Ticket:** IF-11

> **MVP scope:** FR-1 to FR-5; CSV export streamed synchronously (FR-6). SKIP: XLSX, queued exports, notification when ready. Test plan: Pest only (skip Vitest).

## 1. Purpose
Give owners and accountants a fast view of money in, money owed, and who is late, with exportable data.

## 2. User Stories
- As an Owner, I see revenue, outstanding, and overdue totals at a glance.
- As an Accountant, I can see revenue by month and top clients for a date range.
- As an Accountant, I can export invoices and payments to CSV/Excel.

## 3. Functional Requirements
- FR-1: Dashboard KPIs: revenue this month (payments received), total outstanding, overdue amount and count, invoices sent this month
- FR-2: Recent invoices (10) and overdue invoices list (top 5 by days late)
- FR-3: Reports page with date range (default last 12 months):
  - Revenue by month (bar chart)
  - Invoice status breakdown (donut)
  - Top 10 clients by billed amount
  - Receivables aging buckets: 0-30, 31-60, 61-90, 90+ days
- FR-4: `ReportService` uses aggregate SQL (`SUM`, `COUNT`, `GROUP BY`, PostgreSQL `date_trunc`), not PHP loops
- FR-5: Results cached per company; cache busted by `InvoicePaid`, `InvoiceSent`, and `PaymentRecorded`
- FR-6: Export invoices and payments as CSV/XLSX; large exports queued, stored privately, downloaded by signed URL, and the user is notified when ready

## 4. Business Rules
- "Revenue" = sum of payments received in the period (cash basis), not invoice totals
- Outstanding = sum(total - amount_paid) for sent, partially_paid, overdue invoices
- Void invoices excluded from all totals
- Money formatted at the view layer only
- Time zone from company settings determines month boundaries
- Date range max 5 years

## 5. Data Model
Reads `invoices`, `payments`, `clients`. Indexes: `(company_id, status)`, `(company_id, due_date)`, `(company_id, issue_date)`, `payments (company_id, paid_at)`.

## 6. Permissions
Dashboard: all roles (Staff sees counts only, no revenue). Reports and export: Owner, Accountant.

## 7. UI / UX
`StatCard` components, Recharts charts (bar + donut), deferred props with skeletons for slow widgets, date range picker, export button with progress/ready toast. Empty states for no data.

## 8. Edge Cases
- No data: zeros and friendly empty states, no chart errors
- Month boundaries with time zones
- Partial payments counted in revenue when received
- Export of 100k rows: chunked, no memory exhaustion
- Cache stale after a payment: must be busted immediately

## 9. Acceptance Criteria
- [ ] Numbers match hand-calculated fixtures
- [ ] Dashboard query count documented and within budget (target 8 queries or fewer)
- [ ] Cache hit and bust verified
- [ ] Export columns and formatting correct; roles enforced
- [ ] Tenant isolation verified

## 10. Test Plan
Seed deterministic fixtures, assert each KPI, boundary dates, cache tests, `Excel::fake`, authorization, query count assertions.

## 11. Out of Scope
Custom report builder, scheduled report emails, accounting-grade statements (P&L, balance sheet), tax filings.
