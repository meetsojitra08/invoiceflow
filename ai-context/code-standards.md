# Code Standards

Enforced by Pint (PSR-12 + Laravel preset), `tsc --noEmit`, and the agent itself.

## PHP / Laravel
- `declare(strict_types=1);` in `app/`; type hints and return types everywhere; PHP 8.3+ features (enums, readonly, constructor promotion, match)
- Methods about 25 lines max; one responsibility per class; no commented-out code, no `dd()`/`dump()`
- Naming: classes PascalCase, methods camelCase, tables snake_case plural, routes `dot.notation`, jobs verb phrases (`SendInvoiceEmail`), events past tense (`InvoicePaid`), policies `<Model>Policy`, requests `Store<Model>Request`
- Layering: controller (max about 7 lines per action) -> FormRequest -> Policy -> Service -> Model -> Resource -> Inertia page
- Models: relationships, casts, scopes only; no workflows. Always `$fillable`; use `casts()` for enums/dates
- Multi-write operations in `DB::transaction()`; `chunkById()` for large loops; soft deletes for clients and products only
- Migrations: one table each, `down()` implemented, foreign keys explicit, indexes in the same migration, never edit a deployed one
- Validation in Form Requests (array rules); unique/exists scoped by `company_id`
- Authorization: Policy method per action (`viewAny, view, create, update, delete, send, void, recordPayment`); controllers call `$this->authorize()`; Inertia props carry `can` booleans
- Errors: domain exceptions (`InvalidInvoiceTransition`) rendered as friendly messages; never swallow exceptions; log with `company_id`/`invoice_id`; never log secrets
- Jobs: `ShouldQueue`, `$tries`, `backoff()`, `failed()`; pass IDs not models; set tenant first

## Enums
Statuses and roles are backed string enums with `label()` helpers (`InvoiceStatus`, `Role`).

## Money
Integer minor units only. Never `float` for money in PHP or TS. Format only at display (`formatMoney`).

## React + TypeScript
- `strict: true`; no `any`; no `@ts-ignore` without a reason
- Function components and hooks; one component per file; pages in `resources/js/pages`, shared pieces in `components/`
- Data arrives through Inertia props; no `useEffect` fetching of the same data; debounce network calls
- Forms use Inertia `useForm`/`<Form>`; show `errors.field`; disable submit while `processing`
- Routes through Wayfinder helpers, not string URLs
- No business rules in components (totals, permissions, status transitions are server decisions)
- Stable `key`s (IDs) for editable rows; labels on inputs
- Styling with Tailwind utilities and tokens from `ui-context.md`; use shadcn/ui as is

## PostgreSQL
`jsonb`, `timestampTz`, `CHECK` on money, partial unique indexes, `ILIKE`, `date_trunc`, `lockForUpdate()`. Test on PostgreSQL only.

## API
Resources for output, Form Requests for input, `/api/v1` prefix, plural nouns, correct status codes, never return models directly, consistent error JSON.

## Tests (Pest)
Mirror source names; factories for data; `Mail::fake`, `Queue::fake`, `Http::fake`, `Event::fake`; `assertInertia` for pages; `RefreshDatabase`; no real network.

## Git
Conventional Commits with ticket: `feat(IF-4): invoice calculator`. One commit per ticket on `main`.

## Quality gate
```bash
./vendor/bin/pint
./vendor/bin/pest
npm run types
npm run build
```
