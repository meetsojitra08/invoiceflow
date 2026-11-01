# Spec: Products and Services

**Ticket:** IF-3

> **MVP scope:** All requirements. Test plan: Pest only (skip Vitest).

## 1. Purpose
A catalog of billable items with default prices and tax rates to speed up invoicing.

## 2. User Stories
- As an Accountant, I can create products with price and tax rate.
- As Staff, I can pick a product on an invoice to autofill details.

## 3. Functional Requirements
- FR-1: CRUD: name*, description, unit_price*, tax_rate*, is_active
- FR-2: Price entered in major units (e.g. 2000.00), stored in minor units (200000)
- FR-3: Inactive products are hidden from the invoice picker but remain on old invoices
- FR-4: Soft delete; searchable list with pagination

## 4. Business Rules
- unit_price >= 0
- tax_rate between 0 and 100, two decimals
- Editing a product never changes existing invoices (items are snapshots)
- Name unique per company among non-deleted products (PostgreSQL partial unique index `WHERE deleted_at IS NULL`)

## 5. Data Model
`products` tenant scoped, soft deletes.

## 6. Permissions
Owner, Accountant: manage. Staff: read only.

## 7. UI / UX
Table with name, price (formatted), tax %, status badge. Form uses a money input component.

## 8. Edge Cases
- Input like "2,000.50" or "2000.5": normalize to 200050
- Rounding when converting input: use string-based conversion, not float math
- Product deleted while an invoice draft is open: draft keeps its snapshot

## 9. Acceptance Criteria
- [ ] Money stored as integers, displayed formatted
- [ ] Staff cannot create or edit products
- [ ] Old invoices unaffected by product edits
- [ ] Tenant isolation verified

## 10. Test Plan
Money conversion unit tests, CRUD feature tests, policy tests, tenant isolation tests.

## 11. Out of Scope
Stock levels, variants, bundles, multiple price lists.
