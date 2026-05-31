# Git Workflows

## Merge strategy

| Operation | Method | Rationale |
|---|---|---|
| feature → epic | **Squash merge** | one commit per PR, clean epic history |
| epic → main | **Merge commit** | preserves epic's squashed commits, shows feature boundary |
| hotfix → main | **Merge commit** | preserves hotfix context, easy to identify/revert |

Enforced by GitHub rulesets: epic branches accept squash only; main accepts merge commit only.

## Keeping branches up-to-date

**Epic branch:** use `git merge origin/main` (not rebase). Epic is shared — rebase rewrites commits others may have branched from.

**Feature branch:** use `git rebase origin/epic/NNN-name`. Local branch only, safe to rewrite. Do this before opening a PR.

## Release workflow

Tag main after every epic merge or hotfix merge. Use annotated tags (stores tagger, date, message — unlike lightweight tags).

```bash
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3
gh release create v1.2.3 --generate-notes --target main
```

`--generate-notes` auto-generates changelog from PR titles since last tag.

### Versioning (semver)

- `MAJOR` — breaking change
- `MINOR` — new feature (epic merge)
- `PATCH` — bug fix (hotfix merge)

### Flow

```
epic → main (merge commit) → git tag vX.Y.0 → gh release create
hotfix → main (merge commit) → git tag vX.Y.Z → gh release create
```
