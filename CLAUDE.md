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

`main` is protected — direct push blocked, CI must pass before merge.

Always:
1. Create a feature branch: `git checkout -b type/short-description`
2. Commit using `type(scope): description` convention
3. Push branch and open a PR: `gh pr create`
4. Stop there — the human reviews and merges the PR

Never push directly to `main`. Never merge a PR without explicit human instruction.

## API

All routes under `/api/`. Public: `/api/login`, `/api/register`. Everything else requires `ROLE_USER`.

Auth is **stateful token-based**, delivered via cookies with HttpOnly + SameSite + Secure flags. JSON login uses `email` + `password` fields. Session storage backend not yet finalized — planned: Redis.

Controllers live in `src/Controller/Api/`. Entities in `src/Entity/`, repositories in `src/Repository/`.

## Domain Decisions (from ADRs)

**Multi-tenancy**: SaaS — anyone can register, create workspaces, become owner. Data isolation between tenants is critical and must be enforced at the data model level, not just runtime checks. Every new feature must be reviewed for data isolation.

**RBAC**: Roles are **workspace-scoped** (not per-user). Three roles: `owner`, `member`, `guest`. Permissions are additive (no negation). A user can hold different roles across different workspaces.

**Authentication**: Stateful tokens preferred over JWT because tokens must be revocable (JWT can't be revoked without infrastructure). Cookie storage preferred over localStorage (XSS resistance).

## Frontend

`@` alias resolves to `frontend/src/`. HTTPS in dev via custom certs — `FRONTEND_SSL_CERT` / `FRONTEND_SSL_KEY` env vars read by `vite.config.ts`.

Git hooks live in `scripts/hooks/` (no Husky):
- **pre-commit**: ESLint + Prettier on staged `.ts/.tsx`; php-cs-fixer on staged `.php`
- **pre-push**: PHPStan + Doctrine schema validation (runs inside Docker containers)
- **commit-msg**: Conventional Commits format enforced — `type(scope): description` (scope required)

Stack: React 19, React Router 7, TanStack Query 5, shadcn/ui, Tailwind 4, react-hook-form + zod, next-themes, sonner.

### Folder structure (`frontend/src/`)

```
src/
├── main.tsx                   # entry point only — createRoot + render
├── App.tsx                    # providers + router + routes
├── index.css
├── assets/
├── components/
│   └── ui/                    # shadcn primitives only
├── features/                  # one folder per domain, self-contained
│   └── <feature>/
│       ├── components/
│       ├── hooks/
│       ├── queries.ts         # TanStack Query defs
│       └── types.ts
├── layouts/                   # route shells (AuthLayout, AppLayout, etc.)
├── pages/                     # thin route components — compose features, no logic
├── contexts/                  # app-wide React contexts (AuthContext, etc.)
├── lib/
│   ├── api.ts                 # fetch client
│   ├── queryClient.ts         # TanStack QueryClient instance
│   └── utils.ts               # cn() and misc helpers
└── types/                     # shared global types
```

**Rules:**
- New domain = new `features/<name>/` folder. Co-locate components, hooks, queries, types inside it.
- No cross-feature imports. Shared code gets hoisted to `lib/` or `types/`.
- Context rule: if two+ unrelated features import a context, it belongs in `contexts/`, not in a feature.
- `pages/` components are thin — compose features and layouts only, no business logic.

## Agent skills

### Issue tracker

Issues tracked in GitHub Issues (MarouaneFaris/momentum). See `docs/agents/issue-tracker.md`.

### Triage labels

Default label vocabulary (needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix). See `docs/agents/triage-labels.md`.

### Domain docs

Multi-context: `CONTEXT-MAP.md` at root points to `frontend/CONTEXT.md` and `api/CONTEXT.md`; shared ADRs in `docs/adr/`. See `docs/agents/domain.md`.

### Commit convention

Conventional Commits with required scope: `type(scope): description`. Enforced by `scripts/hooks/commit-msg`. See `docs/agents/commit-convention.md`.
