# Laravel Deploy Test

Diagnostic application for testing Strackt's Laravel deployment pipeline. One repo, multiple branches — each branch has a different `composer.json` / config combination that triggers different Strackt detection paths.

## How It Works

1. Each **branch** represents a specific deployment variant (MySQL, PostgreSQL, Redis, Horizon, etc.)
2. The **diagnostic page at `/`** shows what's running and whether it matches what `composer.json` declared
3. Deploy a branch via Strackt → visit the page → verify detection and provisioning worked

## Branch Strategy

The `main` branch contains the shared diagnostic infrastructure. Variant branches diverge with minimal config changes — mostly `composer.json`, `.env.example`, and config file defaults. The diagnostic code is adaptive (already handles mysql/pgsql/sqlite, redis checks, Horizon detection, etc.).

### Branches

| Branch | What it tests | Key changes from main |
|--------|--------------|----------------------|
| `main` | Baseline — SQLite, database cache/queue, PHP ^8.2 | Shared diagnostic code, no external services |
| `laravel-mysql` | MySQL detection via `ext-pdo_mysql` | composer.json: add `ext-pdo_mysql`; .env: `DB_CONNECTION=mysql` |
| `laravel-postgres` | PostgreSQL detection via `ext-pdo_pgsql` | composer.json: add `ext-pdo_pgsql`; .env: `DB_CONNECTION=pgsql` |
| `laravel-redis` | Redis cache & queue detection | composer.json: add `predis/predis`; .env: `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` |
| `laravel-horizon` | Horizon queue worker (3 processes) | composer.json: add `laravel/horizon` + Redis; .env: `QUEUE_CONNECTION=redis` |
| `laravel-full-stack` | All services: MySQL + Redis + Horizon + Scheduler + Node | Everything combined |
| `laravel-minimal` | No external services — SQLite, sync queue, array cache | Stripped composer.json, no `ext-*`, no package.json engines |
| `laravel-statamic` | Statamic CMS modifier detection | composer.json: add `statamic/cms` |
| `laravel-php83` | PHP 8.3 version pinning | composer.json: `require.php: ^8.3` |
| `laravel-php84` | PHP 8.4 version pinning | composer.json: `require.php: ^8.4` |

### Creating a New Branch

1. Branch from `main` (so you inherit all shared diagnostic code)
2. Modify `composer.json` — change `require.php`, add `ext-*`, add packages
3. Modify `.env.example` — change default drivers
4. Run `composer update` if adding packages
5. Commit and push — Strackt should auto-detect the differences

### Merging Improvements Back

When improving diagnostic checks or the UI, make changes on `main` first, then rebase/merge into variant branches. Keep variant branches focused on config-only changes.

## Architecture

```
app/Diagnostics/
├── DiagnosticRunner.php              # Orchestrates all checks, computes summary
├── CheckResult.php                   # Result DTO (status, label, detail, latency)
├── Enums/Status.php                  # Ok / Warning / Error
├── Contracts/DiagnosticCheck.php     # Interface: name() + run(): CheckResult[]
└── Checks/
    ├── EnvironmentCheck.php          # APP_ENV, APP_DEBUG, APP_URL, APP_KEY, caching
    ├── PhpCheck.php                  # Version, SAPI, extensions, OPcache
    ├── DatabaseCheck.php             # Driver, connection, version, charset
    ├── CacheCheck.php                # Driver, SET/GET, Redis-specific
    ├── QueueCheck.php                # Driver, failed jobs, PingJob, Horizon
    ├── FilesystemCheck.php           # Disk, write test, storage symlink
    ├── DeploymentCheck.php           # Git, Laravel version, Composer, Node, Vite
    ├── WebServerCheck.php            # Server software, HTTPS, proxy headers
    └── SchedulerCheck.php            # Cron heartbeat
```

Checks are registered in `AppServiceProvider`. Each check returns an array of `CheckResult` objects. The `DiagnosticRunner` collects them and the view renders them.

## What Needs Adding

### ComposerRequirementsCheck (new)

A check that reads `composer.json` and cross-references declared requirements against actual runtime:

- **PHP version**: `require.php` constraint → compare with `PHP_VERSION` → green if satisfies, red if not
- **PHP extensions**: all `require.ext-*` entries → check `extension_loaded()` for each
- **Key packages**: `laravel/framework`, `laravel/horizon`, `statamic/cms`, `predis/predis` → show detected with versions
- **Package.json engines**: `engines.node` constraint → compare with actual `node --version`

This is the most important addition — it shows "what Strackt should have read" vs "what's actually provisioned."

### Variant Header

Add a section at the top of the diagnostic page showing:
- **Branch name** (from git or a config value)
- **Variant description** — what this branch is testing
- **Expected services** — what Strackt should have provisioned for this branch

### Strackt Detection Mirror

Optional: a check that mirrors Strackt's detection logic, showing what Strackt would conclude from this repo's files. This makes the diagnostic page a complete test harness — you can see both what Strackt should detect and whether the provisioned environment matches.

## Stack

- Laravel 12, Livewire 4, Blaze
- Tailwind CSS v4 via Vite
- SQLite by default (branches may use MySQL/PostgreSQL)
- Dark theme UI (zinc-950 background)

## Commands

```bash
composer install          # Install PHP dependencies
npm install && npm run build  # Build frontend assets
php artisan migrate       # Run migrations
php artisan serve         # Local dev server
php artisan diagnose      # CLI version of the diagnostic page
```

## Conventions

- Follow parent CLAUDE.md for git workflow and voice
- Keep branches minimal — only change what's needed for the variant
- Diagnostic checks should be adaptive, not branch-specific
- All checks should gracefully handle missing services (show error/warning, don't crash)
