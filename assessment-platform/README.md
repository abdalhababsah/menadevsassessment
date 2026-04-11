# Assessment Platform

A production-ready assessment platform for administering technical and RLHF
evaluations, built on **Laravel 13 + Inertia + React + TypeScript**. Supports
multi-type questions (single-select, multi-select, coding with sandboxed
execution, RLHF multi-turn reviews), proctoring (fullscreen enforcement, tab
switch detection, camera snapshots), reviewer dashboards with ranked results,
and a comprehensive audit log.

## Quickstart

Requirements:

- PHP 8.3
- Node 20+
- Composer 2+
- MySQL 8 or MariaDB 10.5+
- Redis 7 (required for queues, cache, sessions, Horizon)

```bash
# Clone and install
composer install
npm install

# Configure
cp .env.example .env
php artisan key:generate
# Edit .env — set DB, REDIS, MAIL, ANTHROPIC_API_KEY

# Migrate + seed (creates super admin + demo quiz)
php artisan migrate:fresh --seed

# Build frontend + run
npm run build
php artisan serve

# In separate terminals:
php artisan horizon     # queue workers
php artisan reverb:start # WebSocket server (optional)
```

Default super admin login (override via env before seeding):

```
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=password
```

## Running the test suite

```bash
php artisan test --compact
```

## Code quality

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — directory structure, layer responsibilities, conventions.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — env vars, Horizon supervisors, Reverb, scheduler, S3, security hardening.
- [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md) — every permission and what it gates.

## Key features

- **Candidate flow** — invitation link → email verification → pre-quiz consent → runner with live timer → confirm-submit → locked submitted screen.
- **Anti-cheat** — fullscreen enforcement with configurable exit threshold that auto-submits the attempt, tab-switch and window-blur detection, copy/paste blocking, periodic camera snapshots to S3.
- **Question types** — single-select, multi-select, coding (executed via a pluggable `CodeRunner` contract — ships with a local dev stub; swap for Judge0 or a sandboxed Docker runner in production), RLHF multi-turn with pre/post-prompt forms, SxS rating, and candidate rewrite.
- **Reviewer dashboards** — ranked results per quiz with suspicious-event flags, drill-down to any attempt, RLHF review UI matching the candidate layout in read-only mode, coding review with re-run + audit-logged score overrides.
- **Audit log** — every privileged action is recorded with actor, IP, target, and a before/after JSON diff. Filterable by actor, action, auditable type, and date range.
- **Admin infra** — Horizon queue supervision with dedicated queues for RLHF generation, coding execution, notifications; Laravel scheduler for auto-submit, cleanup, and snapshot pruning; rate-limited proctoring and public-invitation endpoints.

## License

Proprietary — internal use only.
