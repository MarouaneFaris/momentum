# ADR-011: Workspace identity in URL routing

**Date:** 2026-05-28  
**Status:** Accepted

---

## Context

Momentum is a multi-tenant SaaS where users can belong to multiple workspaces with different roles. The frontend needed a strategy for tracking the "active workspace" — the workspace currently in scope for all UI and API interactions.

Two approaches were considered:

1. **URL-based** — active workspace encoded in the URL path (`/workspaces/{id}/...`)
2. **Context/localStorage-based** — active workspace stored in React state or localStorage; URL stays flat (`/dashboard`, `/projects`)

## Decision

Active workspace identity is always URL-encoded. Frontend routes follow the pattern `/workspaces/{id}/{resource}`.

## Rationale

The API already committed to explicit workspace scoping in every resource URL (`/api/workspaces/{workspaceId}/...`). Mirroring this in the frontend keeps the mental model consistent: workspace scope is never hidden in invisible state.

URL-based routing also provides direct practical benefits: links are shareable, deep links to specific projects and tasks work correctly, and browser history (back/forward) reflects actual navigation between workspaces rather than state transitions.

## Alternatives considered

Context/localStorage-based switching is simpler to implement — no nested route tree, no URL parameters to thread through components. It was rejected because it produces non-shareable URLs, breaks on page refresh if the stored workspace is stale, and contradicts the API's own design where workspace scope is always explicit in the request path.

## Consequences

All authenticated app routes are nested under `/workspaces/:id/`. Components within the workspace shell can read `workspaceId` from URL params via React Router — no prop drilling or context needed for workspace identity.

Post-login redirect reads `lastVisitedWorkspaceId` from localStorage and redirects to the appropriate workspace URL, falling back to the first result from `GET /api/workspaces`. If no workspaces exist, `/` renders an empty state inline.

Future deep links (task permalinks, project sharing) work without extra infrastructure — the workspace context is already in the URL.
