# ADR-004: Multi-tenancy decisions

**Date:** 2026-05-05  
**Status:** Proposed

---

## Context

We were thinking about how the first user of a company/workspace should be created into Momentum.
We needed to make a choice between two different approaches in how this project should be built:

1. As a multi-tenant SaaS
2. As a single-tenant organisation tool

The decision took into account the fact that we didn't want to have per company deployment, and keep it to a single maintained instance instead.

## Decision

We decided to go for the SaaS solution, which seems to be the easier way of building the project and maintaining it.
This will enable us to add a company mode, in a later version, on top of the existing multi-tenant foundation cleanly.
Like this, anyone can register, create a workspace and become its owner.

## Rationale

Making Momentum a multi-tenant product answers the "first user creation" question directly.
This will allow for anybody to create their own account on the app directly.
The downside of it is the heavy burden around the security of the data. It must be absolutely air-tight and tested from the get-go.
This constraint should be built at the data model level, not just on runtime checks.

## Alternatives considered

The other option was a single-tenant solution. This seems to be less flexible in case we wanted to make the project more open later.
This option would also have made us handle the deployment per company, which would require a lot more effort from us than the multi-tenant option.

## Consequences

Any user can access Momentum, create an account and start managing their workspaces.
We need to make sure data isolation is applied on every page/API from the get-go.
As such, new feature **must** be reviewed for data isolation as part of the definition of done.
Any leak of data would be a serious security incident.
This enables us to add the company mode layering cleanly in v2.
