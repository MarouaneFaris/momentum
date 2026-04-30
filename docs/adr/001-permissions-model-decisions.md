# ADR-001: Permissions model decisions

**Date:** 2026-04-30
**Status:** Accepted

---

## Context

As we are thinking about the v1 of the new project, which will handle workspaces/projects/tasks for users individually and as teams.
A critical point was raised about how to handle the permissions for users.
We want to have a simple approach to this on the v1 while keeping in mind that it needs to be upgradable on later versions.

What the product team needs for the v1:
> Minimum three levels: an **owner** (the person who created the workspace, full control), a **member** (can create and manage their own tasks, interact with others'), and a **guest** (read-only or limited access, useful for clients or stakeholders). That covers probably 90% of our early users' needs.

What they don't need:
> Granular per-project permissions, custom roles, permission inheritance across nested structures. That's a v2 conversation once we understand how teams actually use the product.

Here are 3 points that will be discussed in this ADR:
1. The "cumulative" aspect
2. Roles being workspace-scoped
3. The permission granularity

## Decision

We will go for a [RBAC](https://en.wikipedia.org/wiki/Role-based_access_control) approach.

### The cumulative aspect

For the v1, we don't want to make things too complex, roles will only represent a sum of permissions.
If all permissions required to access the data matches, access is granted.

### Roles being workspace-scoped

For now, we will go for **one** role per user for the whole app. Roles per workspaces could come in a later version.
This will require some adjustements, but should permit us to release the v1 faster without compromising on the security.

### The permission granularity

On this point, it will depend on the needs of the v1. If we want to keep things simple and have an mvp ready faster, going for more generic permissions should be better.
The RBAC approach will enable us to split those permissions into smaller permissions in the future.

## Rationale

This decision will enable us to add new permissions, new roles, update roles permissions...
This will be easier to maintain and scale alongside the project needs.
The downside of this decision, is that the matrix of permissions/roles will need to be thorough before hands by the product team.
This will take some time to test, either manually or automatically by writing tests.

## Alternatives considered

An other approach could be to use ACL, but we will probably be stuck when wanting to complexify the roles' permissions.

## Consequences

We will have a database table that will list all permissions, and a table that link permissions to roles.
We need to make sure the available permissions are up-to-date and linked to the correct roles.
We will need to document this matrix and make efforts to update the documentation every time the matrix changes.
This will probably make us write more tests scenarios as the number of permissions/roles grow.
Every new feature should include a permissions review to be validated.
