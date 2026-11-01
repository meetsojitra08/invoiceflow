# Spec: REST API

**Ticket:** IF-12

> **MVP scope:** Token page, endpoints for clients, products, invoices (send, void, pdf), payments, throttle 60/min. SKIP: Scribe docs, Idempotency-Key. Test plan: Pest only (skip Vitest).

## 1. Purpose
The web app uses Inertia; this API is for external systems. Let external systems read and manage clients, products, invoices, and payments securely.

## 2. User Stories
- As a developer, I create an API token and call the API from my own app.
- As an Owner, I can revoke a token instantly.
- As an integrator, I get consistent JSON errors and clear docs.

## 3. Functional Requirements
- FR-1: Token management page: create (name + abilities), shown once, list, revoke
- FR-2: `POST /api/v1/auth/token` (email, password, device_name) returns a token; `DELETE` revokes the current token
- FR-3: Endpoints per `../docs/API.md` for clients, products, invoices (incl. send, void, pdf), and payments
- FR-4: Output via API Resources; input via Form Requests; authorization via the same Policies as the web
- FR-5: Pagination (`per_page` max 100), filtering, sorting
- FR-6: Rate limit 60/min per token, with `429` and `Retry-After`
- FR-8: Money returned as integer minor units with `currency`

## 4. Business Rules
- Tenant derived from the token owner's current company; no tenant parameter accepted
- Token abilities are derived from role (Staff has no token access)
- Business logic reused from services; controllers contain no duplicated rules
- API never exposes internal columns (`public_token`, hashes, etc.)
- All write endpoints idempotent where reasonable (support `Idempotency-Key` header on invoice creation, optional stretch)

## 5. Data Model
`personal_access_tokens` (Sanctum), existing domain tables.

## 6. Permissions
Same matrix as web. Token creation: Owner, Accountant.

## 7. UI / UX
Profile > API Tokens page. Token modal with copy button and a "you won't see this again" warning.

## 8. Edge Cases
- Revoked or expired token returns 401
- Valid token, wrong role returns 403
- Other tenant's ID returns 404, never 403 (avoid leaking existence)
- Validation errors return 422 with the standard shape
- Malformed JSON returns 400/422 with a clear message

## 9. Acceptance Criteria
- [ ] All endpoints covered by feature tests
- [ ] Tenant and role matrix tests pass
- [ ] N+1 free (query count asserted)
- [ ] Rate limit returns 429

## 10. Test Plan
Pest API tests with `Sanctum::actingAs`, per-resource CRUD, validation shape, role/tenant matrix, rate limiting, resource shape snapshot.

## 11. Out of Scope
OAuth2, webhooks to customers, GraphQL, public (unauthenticated) API.
