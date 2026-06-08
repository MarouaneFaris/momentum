# ADR-010: Env file architecture and variable ownership

**Date:** 2026-05-26  
**Status:** Accepted

---

## Context

Momentum is a monorepo with three runtime contexts that all need configuration: the Docker stack (MariaDB, PHP/FrankenPHP, Redis, frontend), the Symfony application running inside the PHP container, and the GitHub Actions CI pipeline. Each context has its own mechanism for loading environment variables, and the project had accumulated duplicated default values across multiple files — compose files, `api/.env`, and CI workflow — with no documented rule for where a given variable belongs.

This caused a concrete bug: the root `.env` and `.env.local` were never loaded by Docker Compose, so all services silently fell back to hardcoded `${VAR:-default}` placeholders in `docker/compose.yaml` regardless of what was written in `.env.local`. See #81 for the full investigation.

This ADR extends [ADR-006](./006-symfony-docker-decisions.md), which covers the monorepo structure and Docker context.

---

## Decision

Root `.env` is the single source of truth for all infrastructure variables shared across services. Each context loads it through its own mechanism. No variable is duplicated across files.

### Variable ownership rules

| File | Owns |
|---|---|
| Root `.env` | All infra vars shared across services: DB credentials, Redis credentials, ports, server name, SSL cert paths, Xdebug mode. Committed defaults — no secrets. |
| Root `.env.local` | Uncommitted local overrides for any root `.env` var. Git-ignored. Never committed. |
| `api/.env` | Symfony-specific vars only: `APP_SECRET`, `CORS_ALLOW_ORIGIN`, `DATABASE_URL` composition. No infra defaults — those come via Docker or CI injection. |
| `api/.env.dev` / `api/.env.test` | Environment-specific Symfony overrides (e.g. `APP_SECRET` per env). |

### Load chain per context

**Docker Compose (dev):**
The Makefile passes `--env-file .env` and, when present, `--env-file .env.local` to every `docker compose` invocation. Docker Compose merges both files (later overrides earlier) and uses the result for `${VAR}` interpolation in compose files. Services receive vars via their `environment:` block — compose files do wiring only, no defaults.

**Symfony inside the PHP container:**
Docker injects vars from the `environment:` block as real OS environment variables. Symfony's DotEnv loader reads real env vars first, then `api/.env`, then `api/.env.${APP_ENV}`. Real env vars always win — fallback values in `api/.env` are never reached inside Docker, which is why `DATABASE_URL` uses bare `${MARIADB_*}` with no `:-default`.

**GitHub Actions CI:**
The CI `api` job runs Symfony directly without Docker. A dedicated step exports `MARIADB_*` from root `.env` into `$GITHUB_ENV` before any `php bin/console` command runs. Symfony then sees them as real env vars with the same precedence as Docker injection. The `tests` job sets `DATABASE_URL` explicitly as a job-level env var (hardcoded to match its service container), so it is unaffected.

### Fallback policy

- **Bare `${VAR}` (no fallback):** all vars defined in root `.env`. A missing var produces a loud `docker compose` error at startup rather than silently using a stale value.
- **`${VAR:-default}` retained for:** vars with no root `.env` entry — `IMAGES_PREFIX` (optional build-time image prefix).

### How to add a new infrastructure variable

1. Add the var with a safe committed default to root `.env`.
2. Reference it as bare `${VAR}` in any compose file that needs it.
3. If CI needs it (i.e. it is read by a `php bin/console` command in the `api` job), add its prefix to the `grep -E` pattern in the "Load shared env defaults" CI step.
4. Override locally via `.env.local` — never commit secrets or personal values.

---

## Rationale

Having one authoritative file for infra defaults eliminates the class of bug where updating `.env.local` has no effect on a running container, and eliminates silent fallbacks that mask misconfiguration. The `--env-file` chain in the Makefile (rather than `--project-directory`) was chosen because Docker Compose v5 resolves all relative paths in compose files (volumes, env_file) relative to the project directory — changing project directory would break existing volume mounts that rely on `docker/` as the base.

---

## Consequences

- A missing required var now fails loudly at `make up` time rather than starting with wrong credentials.
- The `api/.env` `DATABASE_URL` has no fallback values — this is intentional. Do not add `:-defaults` back.
- Adding a new var that CI needs requires a one-line update to the CI workflow alongside the root `.env` change.
- `MERCURE_PUBLISHER_JWT_KEY` and `MERCURE_SUBSCRIBER_JWT_KEY` are now in root `.env` (Mercure fully wired). `CADDY_MERCURE_JWT_SECRET` is retired.
