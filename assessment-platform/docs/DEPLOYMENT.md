# Deployment Checklist

This document covers everything required to run the Assessment Platform in
production. It assumes a Linux host, PHP 8.3, Node 20, Redis 7, MySQL 8, and
Nginx in front of PHP-FPM.

---

## 1. Environment variables

| Var | Required | Purpose |
| --- | --- | --- |
| `APP_KEY` | Yes | `php artisan key:generate` output. |
| `APP_ENV` | Yes | `production`. |
| `APP_URL` | Yes | Canonical URL (used in invitation links, email). |
| `DB_CONNECTION` | Yes | `mysql`. |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Yes | MySQL. |
| `REDIS_HOST` / `REDIS_PASSWORD` / `REDIS_PORT` | Yes | Redis (Horizon, queues, cache, sessions). |
| `QUEUE_CONNECTION` | Yes | `redis`. |
| `CACHE_STORE` | Yes | `redis`. |
| `SESSION_DRIVER` | Yes | `redis`. |
| `MAIL_MAILER` + mailer creds | Yes | Transactional mail (candidate verification, notifications). |
| `FILESYSTEM_DISK` | Yes | `s3` in production. |
| `AWS_*` | Yes (if S3) | Access key, secret, region, bucket for camera snapshots and media. |
| `ANTHROPIC_API_KEY` | Yes (if RLHF) | Claude API key. |
| `ANTHROPIC_DEFAULT_MODEL` | No | Defaults to `claude-sonnet-4-6`. |
| `ANTHROPIC_CONCURRENCY_CAP` | No | Defaults to 40; tune to your rate-limit. |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Yes (if WebSocket) | Reverb credentials. |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | Yes (if WebSocket) | Reverb server location. |
| `HORIZON_DOMAIN` / `HORIZON_PATH` | No | Horizon dashboard location. |
| `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` / `SUPER_ADMIN_NAME` | Yes (first deploy) | Read by `SuperAdminSeeder`. |

---

## 2. First deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
```

`migrate:fresh --seed` is only safe in non-production environments. In
production use `migrate --force` + targeted seeders as needed.

---

## 3. Queue workers (Horizon)

Horizon supervises four queues. Start it with:

```bash
php artisan horizon
```

Put it behind `supervisord` so it restarts on failure:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/assessment-platform/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/horizon.log
stopwaitsecs=3600
```

Queue supervisor topology (from `config/horizon.php`):

| Supervisor | Queue | Processes (prod) | Timeout | Tries |
| --- | --- | --- | --- | --- |
| `default-supervisor` | `default` | 10 | 60s | 3 |
| `rlhf-generation-supervisor` | `rlhf-generation` | 50 | 90s | 5 |
| `coding-execution-supervisor` | `coding-execution` | 20 | 60s | 3 |
| `notifications-supervisor` | `notifications` | 5 | 30s | 3 |

After each deploy:

```bash
php artisan horizon:terminate
```

Horizon will be re-started automatically by supervisord.

---

## 4. Reverb (WebSocket)

Run Reverb on a dedicated port (default 8080):

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Put it behind Nginx with a WebSocket-friendly upstream block. See
[laravel.com/docs/reverb](https://laravel.com/docs/reverb) for the reverse
proxy config.

The candidate UI currently polls for RLHF generation status and proctoring
events. Migrating those hooks to Reverb is tracked as an optimization —
polling works in production and is safe to ship first.

---

## 5. Scheduler

Register the Laravel scheduler as a cron:

```cron
* * * * * cd /var/www/assessment-platform && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks (defined in `routes/console.php`):

| Command | Cadence | Purpose |
| --- | --- | --- |
| `attempts:auto-submit-expired` | Every minute | Auto-submit attempts past their time limit. |
| `invitations:cleanup-expired` | Daily | Revoke invitations expired >30 days. |
| `candidates:cleanup-expired-verifications` | Hourly | Purge unverified candidates older than 7 days. |
| `snapshots:prune-old --days=30` | Daily | Delete camera snapshots past retention. |
| `horizon:snapshot` | Every 5 minutes | Capture Horizon metrics. |

---

## 6. S3 / media storage

Camera snapshots are written to the `local` disk in dev. In production set
`FILESYSTEM_DISK=s3` and configure the AWS credentials. See
`config/filesystems.php`. Grant the IAM user `PutObject`, `GetObject`, and
`DeleteObject` on the snapshot bucket.

---

## 7. Anti-cheat rate limiting

The following routes are rate-limited per default. Adjust in
`routes/web.php` if needed:

| Route | Limit |
| --- | --- |
| `POST /api/quiz/suspicious-event` | 60 / min |
| `POST /api/quiz/camera-snapshot` | 12 / min |
| `POST /quiz/rlhf/prompt-input` | 10 / min |
| `GET /i/{token}` (public invitation) | 30 / min / IP |

---

## 8. Security hardening

- Rotate `APP_KEY` never — it would invalidate every encrypted value.
- Set `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=your-domain`.
- Enable HSTS in Nginx.
- Put Horizon behind IP allow-list (`HORIZON_DOMAIN` + Nginx).
- Restrict `/admin/*` routes behind VPN or SSO if applicable.
- Monitor the audit log (`/admin/audit-log`) for privileged actions.

---

## 9. Smoke test

After deploy, verify:

```bash
php artisan about                         # confirms config
php artisan migrate:status                 # no pending migrations
php artisan horizon:status                 # running
php artisan queue:failed                   # empty
curl -sS https://APP_URL/up                # health check returns 200
```

Then walk a candidate through an invitation → start → submit flow and
confirm scoring + final submit screen appear correctly.
