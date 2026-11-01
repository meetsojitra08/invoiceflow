# Deployment

## Easiest options
| Option | Notes |
|--------|-------|
| VPS (Hetzner, DigitalOcean, Lightsail) + Nginx | Full control; steps below |
| Render / Railway (Docker) | Use their managed PostgreSQL; run the queue worker and scheduler as extra services/cron |

## VPS steps (Ubuntu 24.04)
1. Install: Nginx, PHP 8.3+ FPM (pdo_pgsql, gd, zip), PostgreSQL 16, Composer, Node 22, Supervisor, Git
2. Create DB and user: `CREATE ROLE invoiceflow LOGIN PASSWORD '...'; CREATE DATABASE invoiceflow OWNER invoiceflow;`
3. Clone repo, `cp .env.example .env`, set production values, `php artisan key:generate`
4. `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
5. `php artisan migrate --force`
6. Nginx root -> `public/`; HTTPS with `certbot --nginx -d yourdomain.com`
7. `storage` and `bootstrap/cache` writable by the web user

## Production .env essentials
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_CONNECTION=pgsql
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```
Set Stripe, mail, and AI variables in the server environment only.

## Queue worker (Supervisor)
```ini
[program:invoiceflow-worker]
command=php /var/www/invoiceflow/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/invoiceflow/storage/logs/worker.log
```

## Scheduler (cron)
```
* * * * * cd /var/www/invoiceflow && php artisan schedule:run >> /dev/null 2>&1
```

## Deploy script
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

## CI (`.github/workflows/ci.yml`)
```yaml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env: { POSTGRES_DB: testing, POSTGRES_USER: postgres, POSTGRES_PASSWORD: password }
        ports: ['5432:5432']
        options: >-
          --health-cmd="pg_isready -U postgres" --health-interval=10s --health-timeout=5s --health-retries=5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', extensions: pdo_pgsql, coverage: none }
      - uses: actions/setup-node@v4
        with: { node-version: 22, cache: npm }
      - run: composer install --prefer-dist --no-progress
      - run: npm ci && npm run build && npm run types
      - run: cp .env.example .env && php artisan key:generate
      - run: ./vendor/bin/pint --test
      - run: ./vendor/bin/pest
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: testing
          DB_USERNAME: postgres
          DB_PASSWORD: password
```

## Production checklist
- [ ] `APP_DEBUG=false`, HTTPS on, firewall allows 22/80/443 only
- [ ] Daily `pg_dump` backup
- [ ] Stripe webhook endpoint registered with the live secret
- [ ] Mail provider configured
- [ ] Queue worker and cron running
