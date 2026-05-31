# Issue tracker: GitHub

Issues and PRDs for this repo live as GitHub issues. Use the `gh` CLI for all operations.

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --comments`, filtering comments by `jq` and also fetching labels.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `gh issue comment <number> --body "..."`
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `gh issue close <number> --comment "..."`

Infer the repo from `git remote -v` — `gh` does this automatically when run inside a clone.

## When a skill says "publish to the issue tracker"

Create a GitHub issue.

## When a skill says "fetch the relevant ticket"

Run `gh issue view <number> --comments`.

## Epic branch discovery

Before creating a branch for an issue, check if it belongs to an epic:

```bash
gh api graphql -f query='
{
  repository(owner:"MarouaneFaris", name:"momentum") {
    issue(number: ISSUE_NUMBER) {
      parent { number title }
    }
  }
}'
```

If `parent` is non-null:
- Epic branch = `epic/{parent.number}-{kebab(parent.title)}`
- Base branch on it: `git checkout -b type/scope-description epic/{parent.number}-...`
- PR targets it: `gh pr create --base epic/{parent.number}-...`

If `parent` is null: base on `main`, PR targets `main`.

## PRD / Epic issues

Issues labeled `prd` are documentation — never implement them directly.

When you encounter a `prd` issue:
- Do **not** apply `ready-for-agent` or `ready-for-human`
- Advance the state label instead (see `docs/agents/triage-labels.md`)
- To act on it: break it into sub-issues, then link them (see below)

State transitions:
- After writing/reviewing PRD → move to `prd:ready`
- After spawning all sub-issues → move to `prd:active`
- After epic branch merges to main → move to `prd:complete`

## Linking sub-issues when creating issues from a PRD

After creating each sub-issue, register it under the PRD:

```bash
gh api repos/MarouaneFaris/momentum/issues/{prd_number}/sub_issues \
  --method POST -f sub_issue_id={new_issue_number}
```
