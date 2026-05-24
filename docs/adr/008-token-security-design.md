# ADR-008: Token security design

**Date:** 2026-05-24  
**Status:** Accepted

---

## Context

ADR-002 established stateful token-based authentication but did not document the implementation details of `AuthTokenManager`: how tokens are generated, stored, hashed, and delivered. These decisions carry security implications that must be recorded.

## Decision

### Token generation

Tokens are generated with `bin2hex(random_bytes(32))`, producing a 64-character hex string (256 bits of entropy). The raw token is returned to the caller once and never persisted.

### Storage: SHA256 hash only

Only `hash('sha256', $rawToken)` is stored in the database. A database breach exposes hashes, not usable tokens.

**Why SHA256, not bcrypt:** bcrypt's cost factor exists to slow down brute-force against low-entropy secrets (passwords). Auth tokens are 256-bit random values — the search space makes brute-force infeasible regardless of hashing speed. SHA256 is appropriate and avoids unnecessary latency on every authenticated request.

### TTL

Tokens expire 30 days after creation (`+30 days`). This is hardcoded in `AuthTokenManager::createToken()`.

**Gap:** TTL should be configurable via an environment variable (e.g. `AUTH_TOKEN_TTL`). It is not yet.

### Cookie delivery

Tokens are delivered as cookies with:

- `HttpOnly` — not accessible via JavaScript, mitigates XSS token theft
- `SameSite=Strict` — not sent on cross-site requests, mitigates CSRF
- `Secure` — HTTPS only

This is consistent with ADR-002. The cookie name is `auth_token`.

## Rationale

The combination of high-entropy random generation + hash-only storage + secure cookie flags means:

- A DB breach yields unusable SHA256 hashes of random 256-bit values.
- XSS cannot read the token.
- CSRF cannot use the token cross-site.
- Tokens can be individually revoked (stateful, per ADR-002).

## Alternatives considered

**bcrypt for token hashing:** adds latency on every authenticated request with no security benefit for high-entropy tokens.

**Storing raw token:** simpler, but a DB breach directly exposes valid session tokens.

**localStorage instead of cookies:** prone to XSS; rejected in ADR-002.

## Consequences

- Token lookup requires hashing the incoming raw value before DB query — one SHA256 call per request (negligible cost).
- The 30-day TTL hardcoding is a known gap. A future task should introduce `AUTH_TOKEN_TTL` env var.
- Token rotation (issuing a new token on each request) is not implemented; long-lived 30-day tokens increase exposure window if a cookie is stolen outside the browser context.

## References

- `api/src/Service/AuthTokenManager.php`
- [ADR-002: Authentication decisions](002-authentication-decisions.md)
