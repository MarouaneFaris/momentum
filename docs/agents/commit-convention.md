# Commit Convention

All commits must follow Conventional Commits with a required scope.

## Format

```
type(scope): description
```

Breaking change: append `!` before the colon — `feat(auth)!: drop session cookies`.

## Types

| Type       | When to use                                 |
|------------|---------------------------------------------|
| `feat`     | New feature                                 |
| `fix`      | Bug fix                                     |
| `chore`    | Maintenance (deps, config, tooling)         |
| `docs`     | Documentation only                          |
| `refactor` | Code change — no feature, no fix            |
| `test`     | Adding or updating tests                    |
| `ci`       | CI/CD pipeline changes                      |
| `perf`     | Performance improvement                     |

## Scope

Required. Use the affected area:

- `auth`, `api`, `frontend`, `docker`, `db`, `rate-limiter`, `adr`, `hooks`, `ci`, `deps`

Use the narrowest scope that accurately describes the change.

## Rules

- Description: lowercase, imperative mood, no trailing period
- Subject line ≤ 72 characters
- Body optional — use for non-obvious "why", not "what"

## Examples

```
feat(auth): add token refresh endpoint
fix(rate-limiter): exclude login from consumption
chore(deps): bump symfony to 8.1
docs(adr): add decision for session storage
refactor(api): extract workspace resolver
test(auth): add logout integration test
ci(github-actions): cache composer dependencies
perf(db): add index on workspace_id
feat(auth)!: drop legacy session cookie format
```

## Validation

Enforced by `scripts/hooks/commit-msg`. Skips merge, revert, fixup, and squash commits.
