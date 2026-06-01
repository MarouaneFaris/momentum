# ADR-012: WorkspaceInvitation as a separate entity from UserWorkspace

**Date:** 2026-05-31  
**Status:** Accepted

---

## Context

Workspace membership in Momentum requires two distinct lifecycle phases: an invitation phase (pending, not yet a member) and an active membership phase. The question was whether to model both phases in a single entity or in two separate entities.

`UserWorkspace` already existed to record active memberships. The design choice was whether to extend it with a `status` column and additional invitation-specific fields, or to introduce a dedicated `WorkspaceInvitation` entity.

## Decision

`WorkspaceInvitation` is a separate entity from `UserWorkspace`.

- `UserWorkspace` holds only active memberships. Every row means the user is a current member with a specific role.
- `WorkspaceInvitation` holds only pending invitations. A row is created when an invitation is sent and deleted when the invitation is accepted, declined, or cancelled. There is no status column and no invitation history.

## Rationale

Invitations and memberships have different lifecycles and different fields:

- `WorkspaceInvitation` has `expiresAt`, `invitedBy`, and an expiry-aware "pending" concept that has no equivalent in `UserWorkspace`.
- `UserWorkspace` has `role` and `joinedAt` that only apply to active memberships.

Merging them into a single entity would introduce nullable fields with no long-term value (nullable `expiresAt`, nullable `invitedBy` on active memberships; nullable `joinedAt` on pending invitations). Queries that list active members would need to filter by status, making the invariant implicit rather than structural.

Separate entities keep each model clean and each query obvious. `findAll()` on `UserWorkspace` always returns active members. There is no risk of accidentally including pending invitations in member counts or permission checks.

## Alternatives considered

**Extending `UserWorkspace` with a `status` column** (`pending`, `active`, `declined`): rejected because it bloats the entity with fields that only apply pre-acceptance, violates the single-responsibility principle for the entity, and provides no benefit in v1 where no audit trail of past invitations is required. Declined invitations have no long-term value — the owner can always reinvite.

## Consequences

- Accept, decline, and cancel operations delete the `WorkspaceInvitation` row. No invitation history is kept in v1.
- Reinviting after a decline or expiry is always possible — there is no record of prior declines to check.
- The unique constraint `(workspace_id, invitee_id)` on `WorkspaceInvitation` enforces the "one pending invite per user per workspace" invariant at the database level. Expired invitation rows must be deleted before reinviting (the service layer handles this transparently).
- The `WorkspaceVoter` and `MembershipService` interact only with `UserWorkspace` for permission checks. Pending invitations never grant workspace access.
