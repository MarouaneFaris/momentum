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

In production the SameSite=Strict posture (ADR-008) is preserved by the same-origin bundling decision — API and frontend ship in one container, so no cross-site cookie policy is needed. See [ADR-013](../docs/adr/013-production-deployment-architecture-on-railway.md).

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

### Workspace Name Validation

- Required, min 1 character, max 64 characters
- No character restrictions — any unicode allowed
- No uniqueness constraint (enforced at domain level, not DB)

### Workspace Endpoints

| Method | Route | Auth | Body | Response |
|---|---|---|---|---|
| `GET` | `/api/workspaces` | `ROLE_USER` | — | `200` array of workspace objects |
| `POST` | `/api/workspaces` | `ROLE_USER` | `{ name }` | `201` workspace object |
| `GET` | `/api/workspaces/{id}` | member | — | `200` workspace object |
| `PATCH` | `/api/workspaces/{id}` | owner | `{ name }` | `200` workspace object |
| `DELETE` | `/api/workspaces/{id}` | owner | — | `204` |

Workspace object shape: `{ id, name, createdAt, role }` — `role` is the authenticated user's role in that workspace.

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

*Owner cannot leave — must delete the workspace. Ownership transfer is v2.

### Task Status Transitions

Valid transitions (enforced by backend domain logic, not frontend only):

- `todo` → `in_progress`
- `in_progress` → `done` | `todo`
- `done` → `in_progress`

Invalid transitions are rejected by the API. The frontend may mirror this logic for UX but the backend is authoritative.

### WorkspaceInvitation Entity

Separate entity from `UserWorkspace`. Fields: `id` (UUID v7), `workspace` (FK, CASCADE DELETE), `invitee` (FK → User, CASCADE DELETE), `invitedBy` (FK → User, SET NULL on delete), `role` (WorkspaceRole enum), `expiresAt` (DateTimeImmutable), `createdAt` (DateTimeImmutable).

Unique constraint on `(workspace_id, invitee_id)` — one pending invite per user per workspace.

Design rationale: [ADR-012](../docs/adr/012-workspace-invitation-entity.md).

### Invitation Flow

1. Owner invites by email + role → backend looks up user by email; rejects 422 if not found or already a member or pending invite exists
2. `WorkspaceInvitation` row created with `expiresAt = now + 7 days`; no token — UUID v7 id is the identifier
3. Invitee discovers invitation on their dedicated invitations page via `GET /api/invitations`
4. Invitee accepts: `POST /api/invitations/{id}/accept` (authenticated) → `UserWorkspace` row created, invitation deleted
5. Invitee declines: `POST /api/invitations/{id}/decline` → invitation deleted; owner can reinvite
6. Owner cancels: `DELETE /api/workspaces/{id}/invitations/{invitationId}` → invitation deleted
7. Expired invitations (checked lazily on read): treated as not found; owner must reissue
8. Non-existing user invites are v2 (requires email delivery)

Role change via `PATCH /api/workspaces/{id}/members/{userId}`: `member ↔ guest` only. Sending `owner` as target role returns 422 — ownership transfer is v2. Enforced in service layer, not voter.

### Membership Endpoints

| Method | Route | Auth | Body | Response |
|---|---|---|---|---|
| `POST` | `/api/workspaces/{id}/invitations` | owner | `{ email, role }` | `201` invitation object |
| `GET` | `/api/workspaces/{id}/invitations` | owner | — | `200` array of pending invitations |
| `DELETE` | `/api/workspaces/{id}/invitations/{invitationId}` | owner | — | `204` |
| `GET` | `/api/invitations` | `ROLE_USER` | — | `200` array of pending invitations for current user |
| `POST` | `/api/invitations/{id}/accept` | invitee | — | `204` |
| `POST` | `/api/invitations/{id}/decline` | invitee | — | `204` |
| `GET` | `/api/workspaces/{id}/members` | member | — | `200` array of member objects |
| `PATCH` | `/api/workspaces/{id}/members/{userId}` | owner | `{ role }` | `200` member object |
| `DELETE` | `/api/workspaces/{id}/members/{userId}` | owner | — | `204` |
| `DELETE` | `/api/workspaces/{id}/members/me` | member/guest | — | `204` |

Non-existing user invites are v2 (requires email delivery infrastructure). In v1, invitees must already have an account.

### Membership Response Shapes

**Member object** (returned by `GET /api/workspaces/{id}/members`):
```json
{ "id": "...", "name": "...", "email": "...", "role": "member", "joinedAt": "..." }
```

**Invitation object — owner view** (`GET /api/workspaces/{id}/invitations`):
```json
{ "id": "...", "invitee": { "id": "...", "name": "...", "email": "..." }, "role": "member", "expiresAt": "...", "createdAt": "..." }
```

**Invitation object — invitee view** (`GET /api/invitations`):
```json
{ "id": "...", "workspace": { "id": "...", "name": "..." }, "invitedBy": { "id": "...", "name": "..." }, "role": "member", "expiresAt": "...", "createdAt": "..." }
```

### Notification Triggers (v1)

In-app only via Mercure (no email). Triggers:

- Task assigned to you
- Task status changed (if you are the assignee)
- Invitation received
- Invitation accepted (notifies the owner)

### Mercure (Real-time)

Hub is built into FrankenPHP via the Caddy Mercure module — no separate container. Reachable at `/.well-known/mercure`.

**JWT key separation**: Publisher and subscriber keys are kept distinct. Caddy verifies each side with its own key; the PHP app signs with the matching key.

| Var | Who reads it | Purpose |
|-----|-------------|---------|
| `MERCURE_PUBLISHER_JWT_KEY` | Caddy hub + MercureBundle (`mercure.yaml`) | Signs JWTs used by PHP when publishing updates to the hub |
| `MERCURE_SUBSCRIBER_JWT_KEY` | Caddy hub + `MercureTokenController` | Signs JWTs issued to browsers for SSE subscription |
| `MERCURE_URL` | PHP app | Internal Docker URL (`http://php/.well-known/mercure`) used to publish |
| `MERCURE_PUBLIC_URL` | PHP app | Browser-facing URL (`https://localhost/.well-known/mercure` in dev) |
| `VITE_MERCURE_PUBLIC_URL` | Vite/frontend | Exposed via `import.meta.env`; read by `src/lib/mercure.ts`. Dev: `https://localhost/.well-known/mercure`. Prod: `/.well-known/mercure` (`frontend/.env.production`). Railway: set on the `app` service (see runbook §4.3). |

In dev: both keys share the same value (set in root `.env.local`). In Railway: each key is generated independently — a compromised subscriber key cannot be used to publish.

**Dev extras**: `MERCURE_EXTRA_DIRECTIVES` (compose.override.yaml) enables `anonymous` subscriptions and demo UI at `/.well-known/mercure/ui/`.

**Subscriber JWT issuance**: `GET /api/notifications/mercure-token` — issues an HttpOnly cookie (`mercureAuthorization`) signed with `MERCURE_SUBSCRIBER_JWT_KEY`, scoped to the caller's `/notifications/{id}` topic. TTL: 3600 s.

#### Local debugging

Two tools for testing the SSE → bell → panel flow without triggering real domain events:

1. **Dev Notifications Panel** (recommended) — floating "Dev Notify" dropdown in the frontend (visible only when `import.meta.env.DEV`). Click any of the 7 notification types to fire `POST /api/dev/notifications/trigger`, which persists a notification row for the current user and publishes it via Mercure. The bell badge and panel update in real-time via the existing SSE subscription. Endpoint is gated by `DevService::ensureDevEnvironment()` (only available in `dev`/`test` environments).

2. **Mercure demo UI** (lower-level fallback) — raw subscribe/publish UI at `/.well-known/mercure/ui/` (enabled by the `demo` directive in `MERCURE_EXTRA_DIRECTIVES`). Useful for probing hub connectivity or publishing arbitrary payloads without going through the API.

### Rate Limiting

Two policies via `RateLimitSubscriber`: IP-based fixed window for `/api/register` (10/hr), user-based token bucket for authenticated `/api/*` routes (60/min). `/api/login` and `/api/logout` are excluded — login uses Symfony's built-in `login_throttling` (5 attempts / 15 min). See `docs/adr/007-rate-limiting-strategy.md`.

**Trusted proxy (required in prod):** Set `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR` in the Railway environment. Without it, `Request::getClientIp()` returns Railway's internal edge IP, making the IP-based limiter bucket every user together. `REMOTE_ADDR` is safe because Railway is the sole hop in front of the app — it trusts the direct peer and reads the real IP from `X-Forwarded-For`. If a CDN is later placed in front of Railway, update this value to chain both the CDN's IP ranges and `REMOTE_ADDR`. See [ADR-013 Decision 4](../docs/adr/013-production-deployment-architecture-on-railway.md).

Note: `api/.env.test` uses `SYMFONY_TRUSTED_PROXIES=10.0.0.0/8` instead of `REMOTE_ADDR`. The `REMOTE_ADDR` token is resolved to `$_SERVER['REMOTE_ADDR']` at kernel boot — empty in CLI/test mode — so a CIDR is used in tests while `REMOTE_ADDR` is correct for the prod web context.

See `docs/adr/` for full decision records.

## Testing

See [ADR-009](../docs/adr/009-testing-strategy.md) for strategy decisions. Implementation conventions:

### Directory layout

```
api/tests/
├── Unit/           # plain PHPUnit TestCase — no Symfony, no DB
├── Integration/    # KernelTestCase — DB/Doctrine, no HTTP
└── Functional/     # WebTestCase — full HTTP stack
```

### Base class selection

| Use | Base class |
|---|---|
| Pure PHP functions (no I/O) | `TestCase` |
| Service + DB, no HTTP | `KernelTestCase` |
| Controllers, cookies, headers | `WebTestCase` |

### Mocking policy

Mock at boundaries you do not control; hit real infrastructure you provision in CI.

- **Never mock:** MariaDB, Redis
- **Always mock:** external mailer, external OAuth providers
- **Use `ClockInterface`** (already injected in `AuthTokenManager`) to freeze time and test TTL expiry