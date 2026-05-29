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

First-time setup: `make install` (generates dev certs, builds images, installs git hooks).

Non-obvious commands:
```bash
make sf c="route:list"   # run any Symfony console command
make cc                  # cache:clear shorthand
make dev-certs           # regenerate mkcert HTTPS certs (requires mkcert)
make install-hooks       # re-register git hooks after fresh clone
make node-sh             # shell into frontend container
```

CI runs `make check` (frontend + backend) via GitHub Actions (`.github/workflows/quality.yaml`).

## Git workflow

`main` is protected — direct push blocked, CI must pass before merge. Epic branches (`epic/*`) are equally protected.

### Epic branches

PRDs spawn an epic branch: `epic/{prd-issue-number}-{kebab-title}` (e.g. `epic/125-workspace-crud-and-switching`). Sub-issues from the PRD are implemented on feature branches based off the epic branch and merged back into it via PR. The epic branch merges to `main` when all sub-issues are done.

### Always

1. Check if the issue has a parent (PRD) — see `docs/agents/issue-tracker.md`
2. If parent exists, base your branch on the epic branch: `git checkout -b type/short-description epic/NNN-name`
3. Otherwise base on `main`: `git checkout -b type/short-description`
4. Commit using `type(scope): description` convention
5. Open a PR targeting the epic branch (or `main` if no epic): `gh pr create --base epic/NNN-name`
6. Stop there — the human reviews and merges the PR

Never push directly to `main` or an epic branch. Never merge a PR without explicit human instruction.

## API

All routes under `/api/`. Public: `/api/login`, `/api/register`. Everything else requires `ROLE_USER`.

Auth is **stateful token-based**, delivered via cookies with HttpOnly + SameSite + Secure flags. JSON login uses `email` + `password` fields. Session storage backend not yet finalized — planned: Redis.

Controllers live in `src/Controller/Api/`. Entities in `src/Entity/`, repositories in `src/Repository/`.

See `api/CONTEXT.md` for auth decisions, RBAC, and multi-tenancy rules.

## Frontend

`@` alias resolves to `frontend/src/`. HTTPS in dev via custom certs — `FRONTEND_SSL_CERT` / `FRONTEND_SSL_KEY` env vars read by `vite.config.ts`.

Git hooks live in `scripts/hooks/`:
- **pre-commit**: ESLint + Prettier on staged `.ts/.tsx`; php-cs-fixer on staged `.php`
- **pre-push**: PHPStan + Doctrine schema validation (runs inside Docker containers)
- **commit-msg**: Conventional Commits format enforced — `type(scope): description` (scope required)

See `frontend/CONTEXT.md` for stack, folder structure, and architectural rules.

## Agent skills

### Issue tracker

Issues tracked in GitHub Issues (MarouaneFaris/momentum). See `docs/agents/issue-tracker.md`.

### Triage labels

Default label vocabulary (needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix). See `docs/agents/triage-labels.md`.

### Domain docs

Multi-context: `CONTEXT-MAP.md` at root points to `frontend/CONTEXT.md` and `api/CONTEXT.md`; shared ADRs in `docs/adr/`. See `docs/agents/domain.md`.

### Commit convention

Conventional Commits with required scope: `type(scope): description`. Enforced by `scripts/hooks/commit-msg`. See `docs/agents/commit-convention.md`.
