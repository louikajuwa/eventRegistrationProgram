# Event Registration

Multi-tenant event registration system. Organizations create events with custom registration questions; the public registers through a shareable link; admins track sign-ups on a live dashboard and export reports.

**Stack:** Laravel (PHP 8.3) · MySQL · Redis · queues + scheduler · Socket.IO · Mailpit · Docker

## Features

- Organizations (tenants) with roles and per-tenant data isolation
- Events with capacity limits, status, and custom questions
- Public registration pages (`/e/{slug}`) with duplicate and capacity protection
- Email notifications, live registration counter, and Excel/CSV exports

## Quick start

```bash
cp .env.example .env     # then fill in your own values
make build
make up
make setup               # deps, app key, socket secret, migrate + seed, storage link
```

Open http://localhost:8080.

Run `make help` to see all commands (`migrate`, `fresh`, `seed`, `shell`, `logs`, `pint`, ...).

## Tests

```bash
make test
```

Tests run in isolation (in-memory SQLite, array drivers), so your MySQL data is never touched.

See [CHANGELOG.md](CHANGELOG.md) for recent changes.
