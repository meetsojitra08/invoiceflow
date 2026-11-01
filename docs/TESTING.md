# Testing (lean, Pest only)

Run on PostgreSQL (`invoiceflow_test`), never SQLite.

## Must-have tests
1. `InvoiceCalculator`: rounding, mixed tax rates, discounts, fractional quantities, empty items
2. Tenant isolation: for each tenant model, another company's record returns 404 (web and API)
3. Policies: role x action dataset for invoices, payments, team
4. Invoice status transitions: valid and invalid
5. `PaymentService`: partial, full, overpayment rejected, invalid status rejected
6. Stripe webhook: valid signature processed, bad signature 400, duplicate event creates one payment
7. Scheduler: overdue marking and reminders once each (`Carbon::setTestNow`)
8. API: auth, validation shape, one happy path per resource group
9. AI: `Http::fake` for valid JSON, invalid JSON, timeout, flag off

## Conventions
Factories for data; `RefreshDatabase`; fakes for externals (`Mail`, `Queue`, `Http`, `Event`); `assertInertia` for pages; behavior-style names (`it('prevents staff from voiding invoices')`).

```php
it('does not leak invoices across companies', function () {
    $user = User::factory()->withCompany()->create();
    $other = Invoice::factory()->forOtherCompany()->create();

    $this->actingAs($user)->get(route('invoices.show', $other))->assertNotFound();
});
```

## Quality gate
`./vendor/bin/pint`, `./vendor/bin/pest`, `npm run types`, `npm run build`.
