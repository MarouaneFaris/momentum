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

### API Route Structure

Workspace context is always a URL path parameter. Resource routes are nested under `/api/workspaces/{workspaceId}/`. This makes workspace scope explicit, RESTful, and cacheable. A middleware/voter extracts `workspaceId` from the route and verifies membership before the controller runs.

### Entity PKs

All entities use UUID v7 (`Symfony\Component\Uid\UuidV7`) as primary key. Stored as `BINARY(16)` via Doctrine's `UuidType`. UUID v7 is time-ordered — sequential inserts avoid InnoDB B-tree fragmentation. Generated in PHP via `UuidV7Generator`, not at DB level. Inspect raw values in MariaDB with `HEX(id)`.

### Workspace Domain Rules

- No uniqueness constraint on workspace name — two users can have "My Workspace"
- No direct `ownerId` FK on `Workspace` — current owner resolved via `UserWorkspace WHERE role = 'owner'`
- `creatorId` on `Workspace` is immutable audit only — not used for permission checks
- Users can belong to multiple workspaces; registration auto-creates one default workspace
- Workspace deletion is hard delete — DB-level cascade removes all nested resources (projects, tasks, memberships, invitations)
- Ownership transfer is v2 — in v1, owner's only exit is deleting the workspace
- When a member leaves or is removed, their assigned tasks are unassigned (`assignee_id = null`)
- `GET /api/workspaces` returns all workspaces the authenticated user belongs to (any role), with role included per item
- Invitation acceptance: authenticated `POST /api/invitations/{invitationId}/accept` — invitee must be logged in; backend verifies authenticated user matches invitation target email

### Workspace Permissions Matrix

| Operation | Owner | Member | Guest |
|---|---|---|---|
| View workspace | ✅ | ✅ | ✅ |
| Rename workspace | ✅ | ❌ | ❌ |
| Delete workspace | ✅ | ❌ | ❌ |
| Invite members | ✅ | ❌ | ❌ |
| Remove members | ✅ | ❌ | ❌ |
| Change member role | ✅ | ❌ | ❌ |
| Leave workspace | ❌* | ✅ | ✅ |
| Create projects | ✅ | ✅ | ❌ |
| View projects | ✅ | ✅ | ✅ |

*Owner cannot leave — must transfer ownership or delete workspace first.

### Rate Limiting

Two policies via `RateLimitSubscriber`: IP-based fixed window for `/api/register` (10/hr), user-based token bucket for authenticated `/api/*` routes (60/min). `/api/login` and `/api/logout` are excluded — login uses Symfony's built-in `login_throttling` (5 attempts / 15 min). See `docs/adr/007-rate-limiting-strategy.md`.

See `docs/adr/` for full decision records.
