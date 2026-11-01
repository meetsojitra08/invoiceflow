# Architecture Context (Source of Truth)

## Stack
- Laravel 13, PHP 8.3+, PostgreSQL 16+ (never SQLite/MySQL)
- Frontend: React 19 + TypeScript (strict) + Tailwind CSS 4 + shadcn/ui via Inertia.js; typed routes via Wayfinder (generated, do not edit); charts with Recharts
- Auth: starter-kit auth (Fortify) for the web app; Sanctum tokens for `/api/v1`
- Queue, cache, sessions: `database` drivers. Scheduler via `php artisan schedule:work` (dev) / cron (prod)
- PDF: barryvdh/laravel-dompdf (Blade template). Payments: Stripe Checkout + webhooks. AI: Claude API behind `AiService`
- Tests: Pest. Quality gate: pint, pest, `npm run types`, `npm run build`

## Request flow
Route -> Controller (thin) -> FormRequest (validate) -> Policy (authorize) -> Service (logic, transaction) -> API Resource -> `Inertia::render('page', props)` -> React page in `resources/js/pages`.
Writes: Inertia `useForm` -> Form Request; validation errors return as `errors`.

## Inertia/React contract
- Props come from API Resources; TS types in `resources/js/types` mirror them
- Money in props = integer minor units + currency; React formats with `lib/money.ts` (`formatMoney`, `parseMoneyToMinor`)
- Permissions in props as `can: {...}` booleans (hide buttons only; server enforces)
- Invoice totals: React calls debounced `POST /invoices/calculate`; server uses `InvoiceCalculator`; server recalculates on save and ignores client totals
- Flash messages through shared prop `flash` shown as toasts
- Blade is used only for the Inertia root view, the PDF template, and Markdown mails

## Multi-tenancy (single DB, `company_id` on every tenant table)
- `TenantManager` holds the current company id (set by `SetCurrentCompany` middleware; set explicitly in jobs, commands, listeners)
- `BelongsToCompany` trait: global scope `company_id = current tenant` + auto-set on `creating`; with no tenant set it must fail closed (throw), never return all rows
- Tenant models: Client, Product, Invoice, Payment, AiRequest. InvoiceItem is reached through Invoice. Non-tenant: User, Company, WebhookEvent
- Route model binding resolves through the scope (other tenant's ID = 404)
- Unique/exists rules include `company_id`; cache keys include `company_id`
- Users belong to companies via `company_user.role`

```php
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $q) {
            $q->where($q->getModel()->getTable().'.company_id', app(TenantManager::class)->idOrFail());
        });
        static::creating(fn (Model $m) => $m->company_id ??= app(TenantManager::class)->idOrFail());
    }
}
```

## Roles
| Action | Owner | Accountant | Staff |
|--------|:-----:|:----------:|:-----:|
| Team/settings | Yes | No | No |
| Clients create/edit | Yes | Yes | Yes |
| Clients delete | Yes | No | No |
| Products manage | Yes | Yes | Read only |
| Invoice create/edit draft | Yes | Yes | Yes |
| Invoice send/void | Yes | Yes | No |
| Record payments | Yes | Yes | No |
| Reports/export | Yes | Yes | No |
| API tokens | Yes | Yes | No |

## Money
- `bigint` minor units with `CHECK (>= 0)`; tax % as `decimal(5,2)`; quantity `decimal(10,2)`
- Round half up, once per line, on the tax amount
- Totals computed only by `InvoiceCalculator`; display formatting only in `lib/money.ts` (React) or a formatter in PDF/mail

## Invoice status
`draft -> sent -> partially_paid -> paid`; `draft/sent(no payments) -> void`; `sent/partially_paid -> overdue (scheduler) -> paid/partially_paid`. `paid` and `void` are terminal. Rules live in `InvoiceStatus::canTransitionTo()`; illegal moves throw `InvalidInvoiceTransition`.

## Services (`app/Services`)
`Billing/InvoiceCalculator`, `Billing/InvoiceNumberGenerator` (transaction + `lockForUpdate` on company row), `Billing/InvoiceService`, `Billing/PaymentService`, `Payments/StripeService`, `Ai/AiService`, `Reports/ReportService` (aggregate SQL, cached per company), `Tenancy/TenantManager`.

## Events, jobs, scheduler
- Events: `InvoiceSent`, `PaymentRecorded`, `InvoicePaid`; listeners bust the dashboard cache
- Jobs: `SendInvoiceEmail`, `ProcessStripeWebhook` take `company_id`, call `TenantManager::set()` first, are idempotent, `$tries = 3` with backoff
- Scheduler: `invoices:mark-overdue` daily 01:00, `invoices:send-reminders` daily 09:00 (loop companies, `chunkById`, set tenant per company)

## Webhook flow (must follow)
1. Verify Stripe signature, else 400
2. Insert `webhook_events` (unique provider + event id); duplicate = return 200 and stop
3. Dispatch `ProcessStripeWebhook`, return 200 fast
4. Job: transaction -> `PaymentService::apply()` -> fire `InvoicePaid`
Never mark paid from the success redirect.

## AI rules
Strict JSON output validated before use; AI creates drafts only; log each call to `ai_requests`; feature flag `FEATURE_AI`; daily cap per company; send only the fields the spec allows; app works with the flag off.

## PostgreSQL rules
`jsonb` for JSON; `timestampTz` in UTC; partial unique indexes (`WHERE deleted_at IS NULL`) via `DB::statement`; `ILIKE` search; `date_trunc` grouping; `lockForUpdate()` inside transactions.

## Performance
`Model::preventLazyLoading(! app()->isProduction())`; paginate every list; eager load; composite indexes starting with `company_id`; cache dashboard queries per company.

## Directory map
```
app/{Enums,Jobs,Listeners,Models,Policies,Services,Support/Concerns}
app/Http/{Controllers,Controllers/Api/V1,Requests,Resources,Middleware}
database/{migrations,factories,seeders}   tests/{Feature,Unit}
resources/js/{pages,layouts,components/{ui,shared,invoices},hooks,lib,types}   (+ generated actions/ routes/)
resources/views/{app.blade.php,pdf,mail}
```
