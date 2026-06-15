# ADR-015: Async email dispatch — Symfony Mailer + Messenger Redis transport

**Date:** 2026-06-15  
**Status:** Accepted

---

## Context

Momentum needs transactional email (invitation, password reset, notifications). Two architectural questions arise immediately: how to send, and when to send.

**How:** Symfony Mailer is the natural fit — already in the Symfony stack, supports multiple transports (SMTP, Mailtrap, SES, Postmark) behind a single DSN, and has first-class Messenger integration via `SendEmailMessage`.

**When:** Synchronous send (inline with the HTTP request) is the naive default, but it carries two problems:

1. **Timing oracle for email enumeration.** A "forgot password" or "invite user" endpoint that sends synchronously leaks whether an address is registered: an attacker measures response time — SMTP round-trip takes ~200–500 ms, a no-op returns in <5 ms. Async dispatch removes this signal entirely: every request returns immediately regardless of whether an address exists.

2. **SMTP latency in the request path.** SMTP connections can be slow or fail. Deferring to a worker keeps HTTP response times fast and moves failure handling (retries, dead-letter) out of the request lifecycle.

The existing Redis instance (already wired for rate limiting and sessions — see ADR-007) is available as a Messenger transport. No new infrastructure is needed.

---

## Decision

All email is dispatched asynchronously via Symfony Messenger.

- `MailerInterface::send()` dispatches a `SendEmailMessage` to the `async` Messenger transport (Redis).
- A worker (`messenger:consume async`) processes the queue and delivers via Symfony Mailer.
- Failed messages (after 3 retries with exponential backoff) go to a `failed` transport backed by Doctrine (MariaDB) for durable storage and manual inspection.
- Dev environment uses Mailtrap SMTP (`sandbox.smtp.mailtrap.io`). Production provider is TBD — any Mailer-compatible DSN can be swapped in via env var with zero code change.

---

## Rationale

**Timing-attack mitigation.** Async dispatch makes every email-triggering endpoint constant-time from the caller's perspective. This closes the email enumeration vector without requiring additional middleware or fake-delay hacks.

**Redis as the async transport.** Redis is already running — using it for Messenger adds zero infrastructure. The `${REDIS_URL}/messages` DSN follows the same pattern as other Redis usages in the project.

**Doctrine for the failed queue.** Redis is in-memory; failed messages need durability and human inspection. MariaDB survives restarts and is queryable. The split (high-throughput queue → Redis, dead-letter → DB) is a deliberate asymmetry, not an inconsistency.

**Transport swappability.** The `MESSENGER_TRANSPORT_DSN` env var is the only coupling between business logic and the queue backend. Migrating to RabbitMQ (planned for Phase 4) requires changing one env var — no domain code changes.

**Mailtrap for dev.** Credentials live in `.env.local` (uncommitted). `.env` defaults to `null://null` so the app functions without mail in CI and fresh clones.

---

## Alternatives considered

**Synchronous send:** Rejected. Introduces timing oracle for email enumeration and adds SMTP latency to the request path. No upside over async given Redis is already available.

**Doctrine transport for async queue:** Available, but adds write load to MariaDB for high-throughput cases. Redis is faster and already present. Doctrine is appropriate only for the low-volume failed queue where durability matters more than throughput.

**RabbitMQ for async queue now:** Planned for Phase 4 but not needed yet. Introducing RabbitMQ now adds infrastructure for no immediate benefit. The Messenger abstraction means the migration is a one-line env var change when ready.

**Third-party queue service (SQS, etc.):** Disproportionate for current scale. Revisit if Redis becomes a bottleneck.

---

## Consequences

- All email send calls must go through `MailerInterface` — never direct SMTP or curl.
- Worker must be running for emails to deliver: `bin/console messenger:consume async --time-limit=3600`.
- In production (Railway), the worker needs to run as a separate service — provisioning tracked in #548.
- Failed messages inspectable via `bin/console messenger:failed:show` and retryable via `messenger:failed:retry`.
- Switching to RabbitMQ (Phase 4): change `MESSENGER_TRANSPORT_DSN`, keep routing and retry config unchanged.
- Production Mailer DSN (SES, Postmark, or Mailtrap prod) is set via Railway env var — no committed config change needed.
