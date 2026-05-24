# API Context

Symfony 8.0 (PHP 8.5+) REST API, served by FrankenPHP.

## Structure

- Controllers: `src/Controller/Api/`
- Entities: `src/Entity/`
- Repositories: `src/Repository/`

## Routes

All routes under `/api/`. Public: `/api/login`, `/api/register`. Everything else requires `ROLE_USER`.

## Authentication

Stateful token-based — tokens are revocable (JWT rejected for this reason). Delivered via cookies: HttpOnly + SameSite=Strict + Secure. JSON login uses `email` + `password` fields. Session storage backend planned: Redis.

Token implementation: 256-bit random value (`random_bytes(32)`), raw token never stored — only SHA256 hash persisted. SHA256 chosen over bcrypt because tokens are high-entropy random values (bcrypt cost unnecessary). TTL is 30 days (hardcoded gap: should be `AUTH_TOKEN_TTL` env var). See [ADR-008](../docs/adr/008-token-security-design.md).

## Domain Decisions

### Multi-tenancy

SaaS — anyone can register, create workspaces, become owner. Data isolation between tenants is critical and must be enforced at the data model level, not just runtime checks. Every new feature must be reviewed for data isolation.

### RBAC

Roles are workspace-scoped (not per-user). Three roles: `owner`, `member`, `guest`. Permissions are additive (no negation). A user can hold different roles across different workspaces.

See `docs/adr/` for full decision records.
