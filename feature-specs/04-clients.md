# Spec: Clients

**Ticket:** IF-3

> **MVP scope:** All requirements. No CSV import. Test plan: Pest only (skip Vitest).

## 1. Purpose
Manage the customers a company bills.

## 2. User Stories
- As Staff, I can add, edit, and search clients.
- As an Owner, I can archive a client without losing invoice history.
- As an Accountant, I can see a client's invoices and outstanding balance.

## 3. Functional Requirements
- FR-1: CRUD for clients: name*, email, phone, billing_address, tax_number, send_reminders
- FR-2: List with search (name/email via PostgreSQL `ILIKE`, debounced input in React), sort (name, created), pagination (15)
- FR-3: Detail page shows contact info, invoice history, total billed, outstanding balance
- FR-4: Archive = soft delete; archived clients hidden from lists and pickers but kept on old invoices
- FR-5: Restore archived clients (Owner)
- FR-6: Per-client toggle for automatic reminders

## 4. Business Rules
- Name required, max 255
- Email optional but must be valid and unique per company when present
- A client with unpaid invoices can be archived, but a warning is shown
- Phone stored as text; no strict format

## 5. Data Model
`clients` per `../docs/DATABASE_SCHEMA.md`, tenant scoped, soft deletes.

## 6. Permissions
Create/edit: all roles. Delete/restore: Owner.

## 7. UI / UX
Table with name, email, outstanding balance, actions. Empty state: "No clients yet. Add your first client." Search box above the table.

## 8. Edge Cases
- Duplicate email within company: validation error
- Very long names/addresses: truncate in table, full in detail
- Search with special characters: escaped, no SQL errors
- Archived client's invoices still render correctly

## 9. Acceptance Criteria
- [ ] Full CRUD with validation
- [ ] Search and pagination preserve query string
- [ ] Archive/restore works and respects roles
- [ ] Outstanding balance matches invoice data

## 10. Test Plan
Feature tests for CRUD, validation, search, pagination, soft delete, role permissions, tenant isolation.

## 11. Out of Scope
Client portal login, import from CSV (future), multiple contacts per client.
