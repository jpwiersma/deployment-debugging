# Deployment Debugging Agent Guide

Read `../CLAUDE.md` for fixture strategy, then read `CLAUDE.md` in this repo for branch and diagnostic-page details.

## Scope

This is a Laravel deployment diagnostic fixture. Branches model Strackt detection variants such as MySQL, PostgreSQL, Redis, Horizon, Statamic, scheduler, Node, and PHP version pins.

## Rules

- Keep `main` as shared diagnostic infrastructure.
- Keep variant branches minimal: mostly `composer.json`, `.env.example`, config defaults, and package requirements.
- Diagnostic checks must degrade gracefully when a service is absent.
- UI changes should remain consistent with the existing dark diagnostic style.
- Do not commit generated runtime directories such as `public/vendor/` or `storage/statamic/`.

## Commands

- Install PHP deps: `composer install`
- Build frontend: `npm install && npm run build`
- Migrate: `php artisan migrate`
- Serve locally: `php artisan serve`
- Run diagnostics: `php artisan diagnose`
