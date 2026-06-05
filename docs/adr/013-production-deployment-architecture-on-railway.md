# ADR-013: Production deployment architecture on Railway

**Date:** 2026-06-05  
**Status:** Accepted

---

## Context

ADR-003 chose Railway as the hosting platform and MariaDB as the database. ADR-006 established the monorepo Docker structure with a root build context. Neither document specified how the frontend and API ship together in production, how the database is provisioned on Railway, when schema migrations run relative to a deploy, or how Railway's reverse-proxy layer interacts with Symfony's IP-based rate limiter (ADR-007).

This ADR consolidates the four architectural decisions that must be made before the first production deploy can land. It was driven by the #308 PRD grilling session.

---

## Decision 1: Same-origin frontend bundling

### Decision

The React SPA and the Symfony API ship from one container on the same origin. A `frontend_builder` stage in the Dockerfile runs `vite build` and copies `dist/` into Symfony's `public/` directory. Caddy serves both from that directory: `/api/*` routes to the PHP worker, everything else is handled by a `try_files {path} /index.html` SPA fallback before a `file_server` directive.

### Rationale

ADR-008 mandates `SameSite=Strict` cookies. That posture is only viable when the frontend and API share an origin — a split-origin design would require relaxing to `SameSite=None` and adding `CORS_ALLOW_ORIGIN` configuration. Bundling on the same origin keeps the cookie posture intact without any CORS surface. It also reduces Railway service count, eliminates `VITE_API_URL`, and lets frontend `fetch` calls use relative `/api/*` paths.

### Alternatives considered

**Separate Railway services (frontend CDN + API service):** Requires `SameSite=None; Secure` cookies and an explicit CORS allow-list. Contradicts ADR-008 without a conscious re-decision there. Adds operational complexity (two deploys, two domains, cross-origin cookie debugging).

**Nginx sidecar proxying requests between services:** Adds a reverse-proxy layer that provides no real benefit over Caddy already serving both; more moving parts inside the container.

### Consequences

- The Dockerfile gains a `frontend_builder` stage; build times increase by the time it takes to run `npm ci && vite build`.
- `VITE_API_URL` is not needed in prod — env var absent from the Railway variable set.
- Frontend routes that are not API paths must be excluded from Symfony's routing — ensured by the `try_files` placement before the PHP rewrite.
- Deep-link reloads work correctly because Caddy serves `index.html` for any unknown path, not a Symfony 404.

---

## Decision 2: Self-hosted MariaDB on Railway

### Decision

The production database is a self-hosted MariaDB container on Railway (using the `mariadb:lts` image with a persistent volume mounted at `/var/lib/mysql`), not Railway's native managed MySQL plugin.

### Rationale

ADR-003 chose MariaDB explicitly for its fully open-source lineage. Railway has no managed MariaDB plugin — only a managed MySQL plugin. Substituting MySQL would contradict ADR-003 without a formal re-decision. Self-hosting MariaDB on Railway is the minimum-change path: it preserves the chosen database, keeps the schema and Doctrine type mappings identical, and stays within the same hosting platform.

### Alternatives considered

**Railway managed MySQL plugin:** Violates ADR-003's MariaDB choice. MySQL and MariaDB have diverged in ways that can surface subtle query-behavior differences; switching without validation is a hidden risk.

**Managed database on a separate provider (PlanetScale, Neon, etc.):** Adds a second vendor dependency, complicates networking, and increases cost. Not justified at PoC scale.

### Consequences

- The team owns MariaDB patching and backup scheduling. For PoC, data-loss risk on volume failure is accepted; a backup strategy is a follow-up issue.
- Railway's persistent volume must be attached to the `mariadb` service — a deploy without the volume mount will destroy data.
- DB credentials are shared across services using Railway's shared-variable groups to avoid duplication.

---

## Decision 3: Migration timing via Railway pre-deploy command

### Decision

Doctrine migrations run as Railway's pre-deploy command (`php bin/console doctrine:migrations:migrate --no-interaction`) in a single ephemeral container before the new app release is promoted. A failed migration aborts the deploy; the previous release continues serving traffic. The runtime `docker-entrypoint.sh` does not run migrations.

### Rationale

Running migrations in the entrypoint creates a race: on scale-up, multiple app containers start concurrently and all attempt the same migration. Railway's pre-deploy command runs in exactly one ephemeral container, sequentially, before any app container starts. This eliminates the race, ensures schema and code always match when traffic shifts to the new release, and keeps the entrypoint clean — it only execs FrankenPHP.

A failed migration with this strategy is safe by design: the deploy is aborted, the old schema stays in place, and the old code keeps running. There is no window where new code runs against a half-applied schema.

### Alternatives considered

**Entrypoint migration:** Simple but unsafe under horizontal scale-out (race condition, duplicate migration attempts, potential table locks under concurrent starts).

**Separate migration CI step (before `railway up`):** Possible but requires the migration to run against the production DB from the CI runner, which means exposing DB credentials to CI. The pre-deploy command runs inside Railway's network with no external credential exposure.

**Manual migration before each deploy:** Eliminates automation and is error-prone. Not acceptable for a CD pipeline.

### Consequences

- Moving off Railway later requires porting this step to the new platform's equivalent of a pre-deploy hook or back into the entrypoint with a distributed lock.
- Migrations must be backward-compatible with the previous release for zero-downtime deploys (the old code runs while the new schema is being applied). Non-backward-compatible migrations require a multi-step deploy strategy.

---

## Decision 4: Trusted proxy strategy — `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR`

### Decision

Set `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR` in the Railway production environment. `SYMFONY_TRUSTED_HEADERS` stays at Symfony's default (`x-forwarded-for, x-forwarded-host, x-forwarded-proto, x-forwarded-port`).

### Rationale

Railway terminates TLS at its edge and forwards requests to Caddy with `X-Forwarded-For` carrying the real client IP. Without trusting the proxy, `Request::getClientIp()` returns Railway's internal IP, making ADR-007's IP-based rate limiter useless — it would rate-limit Railway's internal hop rather than real clients.

`REMOTE_ADDR` (Symfony's special value for "trust the direct peer") is safe here because Railway is the sole network hop in front of Caddy. There is no multi-CDN or load-balancer chain that could spoof `REMOTE_ADDR`. If a CDN is added in front of Railway in the future, this setting must be revisited to chain both the CDN and Railway as trusted proxies.

### Alternatives considered

**Hardcoded Railway IP ranges:** Railway does not publish stable egress IP ranges; hardcoding them breaks silently when Railway's network changes.

**`SYMFONY_TRUSTED_PROXIES=*` (trust all):** Insecure — any client can spoof `X-Forwarded-For` and bypass the rate limiter.

**No trusted proxy config:** ADR-007's rate limiter is effectively disabled in prod, since all requests appear to come from Railway's internal IP.

### Consequences

- Adding a CDN or additional reverse-proxy layer in front of Railway requires updating `SYMFONY_TRUSTED_PROXIES` to include the CDN's IP range alongside `REMOTE_ADDR`.
- A functional test covers this: it asserts `Request::getClientIp()` returns the value from `X-Forwarded-For` when the env config sets `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR`.

---

## References

- [ADR-003](./003-stack-and-infrastructure-decisions.md) — stack and hosting choice (Railway, MariaDB); this ADR extends it
- [ADR-006](./006-symfony-docker-decisions.md) — monorepo Docker structure with root build context; this ADR extends it
- [ADR-007](./007-rate-limiting-strategy.md) — IP-based rate limiting; requires trusted proxy to function correctly in prod
- [ADR-008](./008-token-security-design.md) — `SameSite=Strict` cookie posture; same-origin bundling is its prerequisite in prod
- [ADR-010](./010-env-file-architecture.md) — env-var ownership; prod values live exclusively in Railway, not committed files
- [PRD #308](https://github.com/MarouaneFaris/momentum/issues/308) — source of decisions documented here
