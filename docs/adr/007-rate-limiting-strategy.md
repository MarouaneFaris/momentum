# ADR-007: Rate limiting strategy

**Date:** 2026-05-24  
**Status:** Accepted

---

## Context

The API needs protection against abuse. Two distinct threat surfaces exist: unauthenticated registration (no user identity available) and authenticated API usage (user identity always available after login).

Login brute-force is a separate concern handled by Symfony's built-in `login_throttling`.

## Decision

Two independent rate limiting policies implemented in `RateLimitSubscriber`:

| Route pattern | Policy type | Key | Limit | Window |
|---|---|---|---|---|
| `POST /api/register` | Fixed window | Client IP | 10 requests | 1 hour |
| `GET|POST /api/*` (authenticated) | Token bucket | User identifier | 60 requests | 1 minute |

Excluded routes: `/api/login`, `/api/logout`.

Login throttling (separate, Symfony built-in): 5 attempts / 15 minutes.

## Rationale

**Per-IP for `/api/register`:** No auth token exists at registration time — IP is the only available identity. Fixed window is simple and sufficient for signup abuse prevention.

**Per-user for `/api/*`:** Auth token is always present on authenticated routes. Keying by user identifier prevents a single user from exhausting shared IP-based limits (e.g., users behind the same corporate NAT). Token bucket allows short bursts while enforcing a sustained rate.

**Excluding `/api/login`:** Login has its own throttle (`login_throttling`: 5 attempts / 15 min). Applying the API rate limiter on top would double-throttle login and could lock out legitimate users. The subscriber skips unauthenticated requests anyway (returns early if `$token === null`), but the explicit exclusion makes the intent clear.

**Excluding `/api/logout`:** Penalising logout requests would prevent users from cleaning up their session after a rate limit hit, worsening the user experience without security benefit.

## Alternatives considered

Single global IP-based policy: rejected because authenticated users behind shared IPs (corporate offices, VPNs) would hit limits collectively. Per-user keying avoids this.

Stateless middleware (nginx/Caddy): rejected for now — business-logic exclusions (per-user keying, login bypass) are easier to maintain in PHP where the security context is available.

## Consequences

- Unauthenticated requests to non-register, non-login routes are not rate-limited (subscriber returns early if no token).
- Login brute-force protection is decoupled from API rate limiting — each can be tuned independently.
- Future workspace-aware rate limiting (e.g., per-workspace quotas) would require adding a third policy in `RateLimitSubscriber` and a new limiter in `rate_limiter.yaml`.
