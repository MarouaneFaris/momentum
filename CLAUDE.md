# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Monorepo with two apps served via Docker:

- `api/` — Symfony 8.0 (PHP 8.5+) REST API, served by FrankenPHP
- `frontend/` — React 19 + TypeScript + Vite, port 3000
- `docker/` — Docker Compose config; dev overrides in `compose.override.yaml`

Database: MariaDB. Planned but not yet wired: Redis (cache), RabbitMQ (queues).

## Development

All commands go through `make`. Run `make help` for full list.

```bash
make install      # First-time setup: rebuild images + start containers
make up           # Start containers (detached)
make down         # Stop containers
make sh           # Shell into PHP container
make bash         # Bash into PHP container (arrow key history)
make logs         # Tail logs
```

Run Symfony console commands:

```bash
make sf c="route:list"
make cc           # cache:clear
```

Database:

```bash
make migrate-db   # Run pending migrations
make reset-db     # Drop → create → migrate
```

## Quality Checks

```bash
make check        # All checks: frontend + backend
make back-check   # PHPStan (level 6) + php-cs-fixer check
make back-cs-fix  # Auto-fix PHP code style
make front-check  # tsc --noEmit + ESLint + Prettier check
```

CI runs `make check` equivalent via GitHub Actions (`.github/workflows/quality.yaml`).

## API

All routes under `/api/`. Public: `/api/login`, `/api/register`. Everything else requires `ROLE_USER`.

Auth is **stateful token-based** (stored in DB), delivered via cookies with HttpOnly + SameSite + Secure flags. JSON login uses `email` + `password` fields.

Controllers live in `src/Controller/Api/`. Entities in `src/Entity/`, repositories in `src/Repository/`.

## Domain Decisions (from ADRs)

**Multi-tenancy**: SaaS — anyone can register, create workspaces, become owner. Data isolation between tenants is critical and must be enforced at the data model level, not just runtime checks. Every new feature must be reviewed for data isolation.

**RBAC**: Roles are **workspace-scoped** (not per-user). Three roles: `owner`, `member`, `guest`. Permissions are additive (no negation). A user can hold different roles across different workspaces.

**Authentication**: Stateful tokens preferred over JWT because tokens must be revocable (JWT can't be revoked without infrastructure). Cookie storage preferred over localStorage (XSS resistance).

## Frontend

`@` alias resolves to `frontend/src/`. HTTPS enabled in dev via `@vitejs/plugin-basic-ssl`. Husky + lint-staged runs on commit (ESLint + Prettier).

Planned packages not yet installed: ReactRouter, ReactQuery.
