# Spec: Payments

**Ticket:** IF-8, IF-9

> **MVP scope:** All requirements. Without Stripe keys, build against fakes. No refunds. Test plan: Pest only (skip Vitest).

## 1. Purpose
Record manual payments and accept online card payments with reliable, duplicate-safe processing.

## 2. User Stories
- As an Accountant, I can record a full or partial payment (cash, bank transfer).
- As a client, I can pay an invoice online from the public link.
- As an Owner, I can trust that duplicate webhooks never double-count a payment.

## 3. Functional Requirements
- FR-1: `PaymentService::apply(Invoice, amount, method, reference, paidAt)` runs in a DB transaction with a row lock on the invoice
- FR-2: Manual payment modal: amount (defaults to balance), method, reference, date
- FR-3: After applying: update `amount_paid`; status becomes partially_paid or paid; set `paid_at` when fully paid
- FR-4: Fire `PaymentRecorded`; fire `InvoicePaid` when balance reaches zero
- FR-5: Stripe Checkout session created from the public page for the remaining balance, metadata contains `invoice_id` and `company_id`
- FR-6: Webhook endpoint `/webhooks/stripe`: verify signature, store event, queue processing, return 200 fast
- FR-7: `ProcessStripeWebhook` job creates the payment through `PaymentService`
- FR-8: Payments list with filters (date, method, client) and per-invoice payment history
- FR-9: Refunds are not supported in v1 (see section 11)

## 4. Business Rules
- Amount > 0 and <= remaining balance
- Only sent, partially_paid, or overdue invoices accept payments
- Draft and void invoices reject payments
- `webhook_events` unique on (provider, provider_event_id)
- Never mark paid from the success redirect; only from the verified webhook
- Currency of payment must equal invoice currency

## 5. Data Model
`payments`, `webhook_events`, `invoices.amount_paid/paid_at/status`.

## 6. Permissions
Record manual payment: Owner, Accountant. View payments: Owner, Accountant. Online payment: public.

## 7. UI / UX
Invoice detail shows a balance bar (paid vs total) and payment history table. Public page "Pay now" redirects to Stripe. After return, show "Payment processing" until the webhook confirms.

## 8. Edge Cases
- Webhook arrives before the user returns to the site: fine, state updates anyway
- Duplicate or replayed event: ignored, returns 200
- Webhook references unknown/other-tenant invoice: log and ignore safely
- Two payments at the same time (manual + webhook): row lock prevents overpay
- Partial payment via Stripe not offered in v1 (full remaining balance only)
- Job fails mid-way: retried; transaction ensures no half-applied state

## 9. Acceptance Criteria
- [ ] Partial and full payments update status correctly
- [ ] Overpayment and invalid-state payments rejected
- [ ] Bad signature returns 400
- [ ] Same event twice creates exactly one payment
- [ ] `InvoicePaid` fires once

## 10. Test Plan
Service unit/feature tests, Http::fake for Stripe, webhook tests (valid, invalid, duplicate, unknown invoice), concurrency-style sequential tests, role tests.

## 11. Out of Scope
Refunds, chargebacks, subscriptions, multiple gateways, saved cards, payment plans.
