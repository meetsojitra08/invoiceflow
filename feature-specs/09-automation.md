# Spec: Automation (Queues, Scheduler, Events)

**Ticket:** IF-10

> **MVP scope:** FR-2 to FR-5, FR-8 (events + dashboard cache bust), FR-9 basics. SKIP: Horizon, named queues, recurring invoices (FR-6, FR-7). Queue driver is `database`. Test plan: Pest only (skip Vitest).

## 1. Purpose
Remove manual chores: mark overdue invoices, send reminders, generate recurring invoices, and react to key events.

## 2. User Stories
- As an Owner, overdue invoices are flagged automatically.
- As an Accountant, clients get reminders without me chasing them.
- As an Owner, I can set up an invoice that repeats monthly.

## 3. Functional Requirements
- FR-1: Queues on the `database` driver (single default queue)
- FR-2: `invoices:mark-overdue` (daily 01:00): sent/partially_paid invoices with `due_date < today` become overdue
- FR-3: `invoices:send-reminders` (daily 09:00): reminders at 3, 7, 14 days after due date
- FR-4: Each reminder is sent once per invoice per milestone (`invoice_reminders` unique)
- FR-5: Reminders skipped if the client opted out, or invoice is paid/void
- FR-6: Recurring invoices: template + interval (weekly/monthly/yearly) + `next_run_date` + optional `ends_on`
- FR-7: `invoices:generate-recurring` (daily 02:00) creates invoices (as draft or auto-send, per setting) and advances `next_run_date`
- FR-8: Events `InvoiceSent`, `InvoicePaid`, `PaymentRecorded` with listeners: notify Owner, bust dashboard cache
- FR-9: Failed jobs are logged with `company_id` and `invoice_id`; retry with `php artisan queue:retry`

## 4. Business Rules
- All commands iterate companies and set tenant context per company; chunk with `chunkById`
- Commands and jobs are idempotent; running twice on the same day causes no duplicates
- Month-end handling: Jan 31 + 1 month = last day of Feb
- Company time zone decides what "today" means
- Jobs have `$tries = 3` and exponential backoff

## 5. Data Model
`invoice_reminders`, `failed_jobs`, `jobs`.

## 6. Permissions
Reminders toggle per client: Owner, Accountant.

## 7. UI / UX
Invoice detail shows reminder history. "Make recurring" action with interval and end date. Settings page toggle: reminders on/off globally.

## 8. Edge Cases
- Scheduler skipped a day (server down): next run catches up without duplicate reminders
- Invoice paid between queueing and sending: job rechecks state before sending
- Recurring template references archived client: skip and notify Owner
- Mail provider outage: retries, then fails visibly

## 9. Acceptance Criteria
- [ ] Overdue marking correct at date boundaries
- [ ] Reminders sent exactly once per milestone
- [ ] Recurring generation idempotent and handles month-end
- [ ] Events fire and listeners run

## 10. Test Plan
`Carbon::setTestNow` time-travel tests, `Queue::fake`, `Mail::fake`, `Event::fake`, double-run idempotency tests, opt-out tests.

## 11. Out of Scope
SMS/WhatsApp reminders, custom reminder schedules, dunning escalation workflows.
