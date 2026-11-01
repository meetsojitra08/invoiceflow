# Setup

## Requirements
PHP 8.3+ (extensions: mbstring, xml, curl, pdo_pgsql, gd, zip), Composer 2, Node 22 LTS + npm, PostgreSQL 16+, Git. No Redis needed. Laravel Herd (Windows/Mac) installs PHP/Composer/Node in one go.

## Create the project
```bash
composer global require laravel/installer
laravel new invoiceflow     # choose: React starter kit, Laravel auth, Pest, PostgreSQL
cd invoiceflow
php artisan install:api     # Sanctum + routes/api.php
composer require barryvdh/laravel-dompdf stripe/stripe-php
npm install recharts
```
(Installer prompts can differ by version; choose React, built-in auth, Pest, PostgreSQL.) If the target folder already contains files, scaffold in a temp folder and merge into the root.

## Databases
```bash
createdb invoiceflow
createdb invoiceflow_test
```

## .env
```env
APP_NAME=InvoiceFlow
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=invoiceflow
DB_USERNAME=postgres
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=log

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

ANTHROPIC_API_KEY=
AI_MODEL=
FEATURE_AI=true
```
Keep `.env.example` updated with every key (empty values). Never commit `.env`.

## Run
```bash
php artisan migrate --seed
composer run dev            # server + queue worker + Vite
php artisan schedule:work   # scheduler, separate terminal
```

## Commands
| Command | Purpose |
|---------|---------|
| `php artisan migrate:fresh --seed` | Rebuild with demo data |
| `./vendor/bin/pest` | PHP tests |
| `./vendor/bin/pint` | Format PHP |
| `npm run types` | TypeScript check (add `"types": "tsc --noEmit"` to package.json if missing) |
| `npm run build` | Production build |
| `php artisan wayfinder:generate` | Regenerate typed routes |
| `stripe listen --forward-to localhost:8000/webhooks/stripe` | Test webhooks locally |

## Demo data
`DemoSeeder`: 1 company, users `owner@demo.com` (password `password`), an accountant and a staff user, 10 clients, 15 products, 40 invoices in all statuses, payments.
