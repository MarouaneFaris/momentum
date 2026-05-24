# ADR-009: Testing strategy

**Date:** 2026-05-24  
**Status:** Accepted

---

## Context

The project had no automated tests. The CI pipeline listed a "Run tests" job, but it was vacuous — no test runner installed, no tests existed. Auth invariants (token security, revocation, rate limiting) were enforced only by code review, which is advisory, not binding. As a multi-tenant SaaS where a data isolation breach is a security incident (ADR-004), mechanical enforcement is required.

## Decision

### Primary test layer: integration tests

Integration tests are the primary layer. They exercise the full request → security → controller → database path using Symfony's `WebTestCase` or `KernelTestCase`. Tests assert on external behaviour (HTTP status codes, response bodies, DB state after request, cookie headers) — not on internal details (which method was called, which service was instantiated).

Unit tests are reserved for pure domain logic with no framework or DB dependency.

### Tooling

- **PHPUnit** — test runner (`require-dev`)
- **zenstruck/foundry** — factory classes for test fixtures (`UserFactory`, `AuthTokenFactory`); replaces manual `new Entity()` + `em->persist()` boilerplate
- **dama/doctrine-test-bundle** — wraps each test in a transaction that rolls back after, giving isolated tests without truncation overhead

### Database: real MariaDB only

SQLite is explicitly excluded. Entities use `BINARY(16)` UUID storage via Doctrine's `UuidType`, and MariaDB-specific collation behaviour must be exercised in CI. A dedicated `APP_ENV=test` database is used, separate from the dev database.

### Cache/rate limiter: real Redis

Rate limiter state is stored in Redis (ADR-007). Tests that exercise rate limiting hit real Redis — a Redis service container runs alongside MariaDB in CI. Swapping to an in-memory adapter would hide bugs in key expiry behaviour, which is where rate limiter bugs hide.

### Test base class split

Three tiers, chosen per test:

| Tier | Base class | When to use |
|---|---|---|
| Unit | `TestCase` (plain PHPUnit) | Pure PHP functions — no Symfony, no DB |
| Integration | `KernelTestCase` | Needs DB/Doctrine, no HTTP layer |
| Functional | `WebTestCase` | Full HTTP stack — cookies, headers, status codes |

Using `WebTestCase` for everything is an antipattern: it boots the HTTP kernel unnecessarily for service-level tests, slowing the suite and coupling tests to the HTTP contract.

### Directory structure

```
api/tests/
├── Unit/           # TestCase — fastest, no I/O
├── Integration/    # KernelTestCase — DB, no HTTP
└── Functional/     # WebTestCase — full HTTP stack
```

`phpunit.xml` defines separate test suites per directory, enabling `make test-unit` / `make test-functional` for selective runs.

### Dependency mocking policy

Mock at system boundaries you do not control. Do not mock infrastructure provisioned in CI.

| Dependency | Mock? | Reason |
|---|---|---|
| MariaDB | No | Provisioned in CI; UUID behaviour must be real |
| Redis | No | Provisioned in CI; key expiry must be real |
| `ClockInterface` | Yes | Isolates non-determinism; Symfony already supports it |
| External mailer (future) | Yes | Not provisionable in CI |
| External OAuth (future) | Yes | Not provisionable in CI |

`AuthTokenManager` already injects `ClockInterface` — freeze the clock in tests to verify TTL expiry without waiting 30 days.

### Minimum test bar per vertical slice

Every workspace-scoped feature slice must include at minimum:

1. **Happy path** — the authorised actor performs the action and gets the expected response
2. **Tenant isolation** — an authenticated user from a different workspace cannot access the resource (expect 403 or 404)
3. **Role gate** — an actor with insufficient role within the correct workspace cannot perform the action (expect 403)

Auth endpoints (`/api/login`, `/api/register`, `/api/logout`) are global — no workspace context. Their bar is: happy path + auth failure cases (wrong credentials, expired token, revoked token). Tenant isolation and role gate do not apply to them.

This bar makes the existing DoD items ("permissions review" and "workspace data isolation review") mechanically enforceable rather than advisory, consistent with ADR-004's requirement that data isolation be verified, not assumed.

### Token revocation model

`LogoutSubscriber` hard-deletes the `auth_token` row on logout. This is the v1 choice: simple, correct, no audit trail. A future "active sessions" UI would require soft revocation (`revokedAt` column) — deferred to v2 if needed.

## Rationale

**Integration-first over unit-first:** the highest-risk code (auth, rate limiting, tenant isolation) lives at the boundary between framework, DB, and HTTP. Unit tests at that boundary mock away the integration — exactly where bugs live. Integration tests catch real failures.

**Real MariaDB, not SQLite:** `BINARY(16)` UUID storage and MariaDB collation behaviour are not reproducible in SQLite. A test suite that passes on SQLite but fails on the production engine gives false confidence.

**Real Redis, not in-memory adapter:** rate limiter correctness depends on Redis key expiry semantics. An array adapter cannot reproduce this.

**Three-tier split:** avoids the common antipattern of using `WebTestCase` for everything. Pure function tests run in milliseconds. Keeping them separate means the fast feedback loop stays fast.

## Alternatives considered

**Unit tests as primary layer:** rejected. The codebase has few pure domain objects. Most logic lives in service classes wired to Doctrine, the Symfony security layer, and HTTP. Mocking those dependencies produces tests that pass even when the integration is broken.

**SQLite for CI:** rejected. See above.

**In-memory cache adapter for rate limiter tests:** rejected. Does not reproduce Redis key expiry semantics.

**Single flat `tests/` directory:** simpler, but cannot run tiers selectively. Rejected in favour of the three-tier split.

## Consequences

- CI provisions both a MariaDB and a Redis service container for the test job.
- Each test run uses a dedicated `APP_ENV=test` database — running tests never affects dev data.
- `dama/doctrine-test-bundle` transaction rollback keeps tests isolated without truncation; tests are fast even with real DB.
- `createClearCookie()` was found to set `expire: 0` (session cookie) rather than a past timestamp — this is a known bug fixed alongside the first unit test (issue #54). Correct behaviour: `expire: 1` forces immediate cookie expiry on the client.
- Future feature slices must include the three-point test bar before merge. This is enforceable via PR review against this ADR.
- Soft token revocation (audit trail, active sessions UI) is not implemented in v1. Migration path when needed: `ALTER TABLE auth_token ADD revoked_at DATETIME NULL`.

## References

- [ADR-002: Authentication decisions](002-authentication-decisions.md)
- [ADR-004: Multi-tenancy decisions](004-multi-tenancy-decisions.md)
- [ADR-007: Rate limiting strategy](007-rate-limiting-strategy.md)
- [ADR-008: Token security design](008-token-security-design.md)
- `api/src/Service/AuthTokenManager.php`
- `api/src/EventListener/LogoutSubscriber.php`
- `api/src/Security/TokenAuthenticator.php`
