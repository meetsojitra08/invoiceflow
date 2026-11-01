# Spec: Invoices

**Ticket:** IF-4, IF-5

> **MVP scope:** All requirements, including duplicate. Test plan: Pest only (skip Vitest).

## 1. Purpose
Create accurate invoices with line items, taxes, and a controlled lifecycle.

## 2. User Stories
- As Staff, I can create a draft invoice with several items and see totals update live.
- As an Accountant, I can send, void, and track invoices.
- As an Owner, I can filter invoices by status, client, and date.

## 3. Functional Requirements
- FR-1: Invoice fields: client*, issue_date*, due_date*, items*, notes, currency (company default)
- FR-2: Items: description*, quantity*, unit_price*, tax_rate, discount; optional product link for autofill
- FR-3: `InvoiceCalculator` computes subtotal, discount_total, tax_total, total; the UI gets live totals from a debounced `POST /invoices/calculate` (no totals logic in TypeScript)
- FR-4: Number assigned on creation: `<prefix>-<4+ digit sequence>` per company, race-safe
- FR-5: Status lifecycle: draft, sent, partially_paid, paid, overdue, void
- FR-6: Only drafts are editable; sent invoices are read-only
- FR-7: Void requires a reason; allowed from draft or sent (with no payments)
- FR-8: List page with status tabs, search (number/client), date filter, pagination
- FR-9: Duplicate invoice action creates a new draft copy

## 4. Business Rules
- due_date >= issue_date
- At least one item; quantity > 0; unit_price >= 0; discount <= line amount
- All money integer minor units; tax rounded half-up per line
- Totals always recomputed on the server; client-sent totals are ignored
- Item rows submit nested fields (`items.0.quantity`); validation errors map back to the correct row
- Items snapshot description, price, and tax at save time
- `paid` and `void` are terminal states
- Invoice numbers are never reused, even after void or delete

## 5. Data Model
`invoices`, `invoice_items` per `../docs/DATABASE_SCHEMA.md`.

## 6. Permissions
Create/edit draft: all roles. Send/void: Owner, Accountant.

## 7. UI / UX
React invoice builder (Inertia `useForm`) per `../ai-context/ui-context.md`. Detail page shows status badge, items, totals, payments, and action buttons (Send, Download PDF, Record payment, Void, Duplicate), shown only if allowed.

## 8. Edge Cases
- Two users create invoices at the same moment: numbers stay unique and sequential
- Fractional quantities (1.5 hours) and rounding differences
- Client archived after invoice creation: invoice still renders
- Editing a sent invoice via crafted request: server rejects (403/422)
- Zero-total invoice: allowed only if explicitly confirmed (default: block)

## 9. Acceptance Criteria
- [ ] Totals match hand-calculated examples in tests
- [ ] Number generator race-safe
- [ ] Illegal transitions throw and are shown as friendly errors
- [ ] Only drafts editable
- [ ] Tenant isolation and role tests pass

## 10. Test Plan
- Unit: InvoiceCalculator (10+ cases), status transition matrix
- Feature: create/edit/send/void/duplicate, validation, number uniqueness
- Pest `assertInertia`: page component and props shape

## 11. Out of Scope
Credit notes, multi-currency conversion, attachments, per-line custom fields.
