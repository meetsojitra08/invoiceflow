# Spec: Authentication and Workspaces

**Ticket:** IF-2

> **MVP scope:** FR-1 to FR-6. Skip email verification (FR-7). Test plan: Pest only (skip Vitest).

## 1. Purpose
Let users register, log in, and automatically get a company workspace (tenant) where they are the Owner.

## 2. User Stories
- As a visitor, I can register with my name, email, password, and company name.
- As a user, I can log in, log out, and reset my password.
- As a user in several companies, I can switch the active company.

## 3. Functional Requirements
- FR-1: Registration form fields: name, email, password, password confirmation, company name
- FR-2: Registration creates `user`, `company`, and `company_user` (role = owner) in one transaction
- FR-3: `users.current_company_id` is set to the new company
- FR-4: Login redirects to `/dashboard`; guests are redirected to `/login`
- FR-5: Password reset by email link (starter kit / Fortify default)
- FR-6: Company switcher in the top bar lists the user's companies; switching updates `current_company_id`
- FR-7: Email verification enabled (can be disabled in local env)

## 4. Business Rules
- Email unique across the system
- Password minimum 8 characters
- A user can only switch to companies they belong to
- Company defaults: currency INR, invoice prefix `INV`, next number 1

## 5. Data Model
`users` (+`current_company_id`), `companies`, `company_user (role)`.

## 6. Permissions
Public: register, login, reset. Authenticated: switch company, edit profile.

## 7. UI / UX
Inertia/React auth pages from the starter kit (customized). Register page shows the Company name field. Top bar shows a company dropdown only if the user has 2+ companies.

## 8. Edge Cases
- Company creation fails, so the user must not be created (rollback)
- User removed from the active company, so fall back to another company or show a "no workspace" page
- Duplicate email returns inline validation error
- Switching to a company the user does not belong to returns 403

## 9. Acceptance Criteria
- [ ] Registration creates user, company, Owner membership atomically
- [ ] Guests cannot open app routes
- [ ] Switching companies changes visible data
- [ ] Rolled-back registration leaves no orphan rows

## 10. Test Plan
- Feature: register success, validation errors, transaction rollback (force exception)
- Feature: login/logout, guest redirect, switch company allowed/denied
- Feature: reset password flow (Notification::fake)

## 11. Out of Scope
Social login, 2FA, SSO.
