# ADR-014: Deployment pipeline — tag-based prod deploys

**Date:** 2026-06-09  
**Status:** Accepted

---

## Context

ADR-013 established the Railway production architecture and defined the deploy CI job. At that point, the job fired on every push to `main`. Every squash-merged PR shipped to production automatically, with no deliberate release act. For a solo/small team this is too aggressive: it conflates "code is merged" with "we intend to ship now", and leaves no clean audit trail of intentional releases.

---

## Decision

Production deploys trigger on `v*.*.*` tag pushes, not on merge to `main`.

The CI workflow `on:` trigger gains a `tags: ["v*.*.*"]` entry alongside the existing `branches: [main]` entry. The deploy job's `if:` condition changes from `github.ref == 'refs/heads/main'` to `startsWith(github.ref, 'refs/tags/v')`. All quality and test jobs run on tag-triggered CI runs (their `if:` conditions include `startsWith(github.ref, 'refs/tags/v')`), so the deploy job's `needs:` gate is preserved: quality + tests must pass before deploy fires.

No staging environment is introduced.

---

## Rationale

**Deliberate and traceable releases.** A tag push is an explicit act. `git log --tags --simplify-by-decoration --pretty="format:%d %s"` gives a clean release history that `git log main` does not.

**`main` as known-good, not always-shipping.** Merging to `main` means "this passed review and CI" — not "ship now". Decoupling merge from deploy lets multiple PRs accumulate on `main` before a release, and lets the team choose *when* to ship without creating a dummy commit.

**No staging environment.** Local Docker Compose with prod-parity config is sufficient for validating epics before they merge to `main`. A Railway staging service adds monthly cost and an extra deploy target to maintain, for marginal benefit at this project's scale. If the team grows or a formal QA gate becomes necessary, staging can be reconsidered then — see Consequences below.

---

## Alternatives considered

**Deploy on every merge to `main` (status quo):** Simple but conflates "merged" with "released". No clean release history. Accidental partial-feature deploys possible if an epic's sub-issues land across several merges.

**Deploy on merge to `main` with a manual approval gate in GitHub Actions:** Keeps the workflow simple but approval fatigue sets in quickly for a solo developer. The tag approach is lower-friction: one `git tag` command, one push.

**Semantic-release / automated tagging tooling:** Adds CI complexity and a dependency. Manual tagging is appropriate at this project scale — the overhead is one command per release.

**Staging environment:** Ruled out as disproportionate cost and operational overhead for the current team size. Not a permanent non-decision — revisit if a QA gate is needed or the team grows.

---

## Consequences

- Deploying requires: `git tag vX.Y.Z && git push origin vX.Y.Z`
- Tags should be pushed **after** the epic PR merges to `main` — the tag always points to a merge commit on `main`.
- CI continues to run on every push to `main` (quality gate), but no deploy fires.
- `git log --tags` provides a clean, human-readable release history.
- Railway pre-deploy command (Doctrine migrations) runs on tag-triggered deploys as before — no change to the Railway configuration.
- Staging environment deliberately absent. Revisit when team grows or QA gate is needed.
