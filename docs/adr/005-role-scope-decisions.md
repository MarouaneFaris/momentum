# ADR-005: Role scope decisions

**Date:** 2026-05-05  
**Status:** Accepted

---

## Context

In ADR [001-permissions-model-decisions.md](./001-permissions-model-decisions.md), we had initially decided to go for one role per user for the whole app.  
Since then, we decided to make Momentum a multi-tenant SaaS (see [004-multi-tenancy-decisions.md](./004-multi-tenancy-decisions.md)).  
This decision is making us rethink our role per user strategy.

## Decision

We will not go for a role per user, but go for a role **per workspace** instead.  
We will still have 3 roles: owner, member and guest.  
The permissions still follow a RBAC model, are additive and have no negation for now.  
This means that I am the owner of my workspaces, and I can also be a member or a guest of a workspace I was invited to.

## Rationale

To facilitate the cooperation of users between workspaces, scoping the role per workspace is a requirement.  
This will permit users to manage their own workspaces freely, and still be able to be invited to others' workspaces.  
The main drawback from this choice is the complexity of having multiple roles to handle in the codebase.

## Alternatives considered

The alternative was to keep following the decisions of [001-permissions-model-decisions.md](./001-permissions-model-decisions.md) concerning the role per user.  
At this point in time, this decision can't be justified anymore.

## Consequences

ADR [001-permissions-model-decisions.md](./001-permissions-model-decisions.md) is superseded by the current ADR.  
Roles become workspace-scoped.  
A user can have multiple roles.
