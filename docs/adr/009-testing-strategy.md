# ADR-009: Testing strategy

**Date:** 2026-05-24  
**Status:** Accepted

---

## Context

The project had no automated tests. The CI pipeline listed a "Run tests" job, but it was vacuous — no test runner installed, no tests existed. Auth invariants (token security, revocation, rate limiting) were enforced only by code review, which is advisory, not binding. As a multi-tenant SaaS where a data isolation breach is a security incident (ADR-004), mechanical enforcement is required.

## Decision

**Integration tests are the primary layer.** They exercise the full request → security → controller → database path and assert on external behaviour (HTTP status codes, response bodies, DB state, cookie headers). Unit tests are reserved for pure domain logic with no framework or DB dependency.

**Real infrastructure in CI, no substitutes.** Tests run against real MariaDB and real Redis — the same engines used in production. SQLite and in-memory cache adapters are explicitly excluded.

**Minimum test bar per vertical slice:**

- Workspace-scoped endpoints: happy path + tenant isolation (different workspace → 403/404) + role gate (insufficient role → 403)
- Auth endpoints (`/api/login`, `/api/register`, `/api/logout`): happy path + auth failure cases only — no workspace context exists on these routes

This bar makes the existing DoD items ("permissions review" and "workspace data isolation review") mechanically enforceable rather than advisory.

**Token revocation is hard delete.** `LogoutSubscriber` removes the `auth_token` row on logout. No audit trail in v1.

## Rationale

**Integration-first:** the highest-risk code (auth, rate limiting, tenant isolation) lives at the boundary between framework, DB, and HTTP. Unit tests at that boundary mock away the integration — exactly where bugs live.

**Real MariaDB:** entities use `BINARY(16)` UUID storage. MariaDB collation behaviour is not reproducible in SQLite. A suite that passes on SQLite but fails on the production engine gives false confidence.

**Real Redis:** rate limiter correctness depends on Redis key expiry semantics. An in-memory adapter cannot reproduce this.

**Hard delete:** simple and sufficient for v1. A "view active sessions" UI would require soft revocation — deferred to v2 if needed.

## Alternatives considered

**Unit tests as primary layer:** rejected. Most logic is wired to Doctrine, Symfony security, and HTTP. Mocking those produces tests that pass even when the integration is broken.

**SQLite for CI:** rejected. See above.

**In-memory cache adapter for rate limiter tests:** rejected. Does not reproduce Redis key expiry semantics.

**Soft token revocation (`revokedAt` column):** deferred. No audit or session-listing requirement in v1.

## Consequences

- CI provisions MariaDB and Redis service containers for the test job.
- Each test run uses a dedicated `APP_ENV=test` database — running tests never affects dev data.
- Future feature slices must include the minimum test bar before merge.
- Soft token revocation migration path when needed: `ALTER TABLE auth_token ADD revoked_at DATETIME NULL`.

## References

- [ADR-004: Multi-tenancy decisions](004-multi-tenancy-decisions.md)
- [ADR-007: Rate limiting strategy](007-rate-limiting-strategy.md)
- [ADR-008: Token security design](008-token-security-design.md)
