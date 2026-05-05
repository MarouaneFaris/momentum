# ADR-004: Multi-tenancy decisions

**Date:** 2026-05-05  
**Status:** Proposed

---

## Context

We were thinking about how the first user of a company/workspace should be created into Momentum.
We needed to make a choice between two different approches in how this project should be build:

1. As a SaaS, allowing for anyone to register an account then creating his workspaces
2. As a signle organisation tool, to be deploy for each company

## Decision

We decided to go for the SaaS solution, which seems to be the easier way of building the project and maintaining it.
This will enable us to add a company mode, in later version, on top of the existing multi-tenant fondation cleanly.

## Rationale

Making Momentum a multi-tenant product answers the "first user creation" question directly.
This will allow for anybody to create his own account on the app directly.
The downside of it is the heavy burden around the security of the data. It must be absolutely air-tight and tested from the get go.

## Alternatives considered

The other option was a single-tenant solution. This seems to be less flexible in case we wanted to make the project more open later.
This option would also have made us handle the deployement per company, which would require a lot more effort from us than the multi-tenant option.

## Consequences

We need to make sure data isolation is apply on every pages/apis since the get go.
Any leak of data would be a serious security incident.
Any user can access Momentum, create an account and start managing his workspaces.
