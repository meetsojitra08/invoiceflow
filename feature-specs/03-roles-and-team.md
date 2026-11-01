# Spec: Roles and Team

**Ticket:** IF-6

> **MVP scope:** Roles, policies, team page (list, change role, remove) and invitation as a copyable link shown in the UI (no email sending). Skip resend/revoke UI and expiry cleanup. Test plan: Pest only (skip Vitest).

## 1. Purpose
Control what each team member can do and let Owners invite people to the workspace.

## 2. User Stories
- As an Owner, I can invite a teammate by email and choose their role.
- As an Owner, I can change a member's role or remove them.
- As an Accountant or Staff member, I only see actions my role allows.

## 3. Functional Requirements
- FR-1: `Role` enum: owner, accountant, staff
- FR-2: Policies for Client, Product, Invoice, Payment enforce the matrix below
- FR-3: Team page lists members with role and joined date
- FR-4: Invite form: email + role; creates `invitations` row with random token, expires in 48h
- FR-5: Invitation email contains an accept link
- FR-6: Accept flow: existing user logs in and joins; new user registers (no new company) and joins
- FR-7: Owner can resend or revoke pending invitations
- FR-8: UI hides forbidden actions with `@can`, and the server still enforces them

## 4. Business Rules
- A company must always have at least one Owner
- Only Owners manage team, settings, and delete clients
- An email cannot have two pending invitations for the same company
- Expired or used tokens are rejected
- Users cannot change their own role

## 5. Data Model
`invitations (company_id, email, role, token, expires_at, accepted_at)`, `company_user.role`.

## 6. Permissions

| Action | Owner | Accountant | Staff |
|--------|:-----:|:----------:|:-----:|
| Manage team/settings | Yes | No | No |
| Clients create/edit | Yes | Yes | Yes |
| Clients delete | Yes | No | No |
| Products manage | Yes | Yes | Read only |
| Invoice create/edit draft | Yes | Yes | Yes |
| Invoice send/void | Yes | Yes | No |
| Record payments | Yes | Yes | No |
| Reports/export | Yes | Yes | No |
| API tokens | Yes | Yes | No |

## 7. UI / UX
Team page with members table, "Invite member" modal, role dropdown, remove button with confirm dialog. Pending invitations table below.

## 8. Edge Cases
- Last Owner tries to leave or be demoted: blocked with a clear message
- Invitee already a member: show a friendly error
- Invited email differs from logged-in user's email on accept: require matching email
- Direct URL access to a forbidden page returns 403

## 9. Acceptance Criteria
- [ ] Every cell of the matrix is enforced and tested
- [ ] Invitation expires after 48h
- [ ] Last-Owner protection works
- [ ] UI hides forbidden buttons and server still returns 403

## 10. Test Plan
Pest dataset: role x action x expected result. Mail::fake for invites. Time travel for expiry. Edge-case tests for last Owner.

## 11. Out of Scope
Custom roles, per-user permissions, SSO.
