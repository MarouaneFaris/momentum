# Ralph Loop — Momentum Dev Agent

## Your mission each iteration

Pick the highest-priority unfinished `ready-for-agent` issue, implement it fully, verify it passes all checks, commit it, and open a PR. One issue per iteration. Stop when no eligible `ready-for-agent` issues remain.

---

## Step 1 — Pick one issue

Fetch all open `ready-for-agent` issues, excluding v2 work:

```bash
gh issue list --label "ready-for-agent" --state open \
  --json number,title,body,labels \
  --jq '[.[] | select(.labels | map(.name) | contains(["v2"]) | not) | {number, title, body, labels: [.labels[].name]}]'
```

Read each candidate fully (title + body + comments). Then **estimate priority** based on:
- Foundational work unblocks other issues → higher priority
- Small, self-contained, low-risk → prefer over large/risky if value is equivalent
- Issues already partially implemented → higher priority (finish what's started)
- Features closer to the current epic's goal → higher priority

Pick the single highest-priority issue. Read it fully:

```bash
gh issue view <number> --comments
```

If no eligible issues exist → output:

```
<promise>NO READY ISSUES</promise>
```

---

## Step 2 — Discover epic branch

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

- If `parent` non-null → epic branch = `epic/{parent.number}-{kebab(parent.title)}`
- If `parent` null → base on `main`

Fetch the epic branch or main:

```bash
git fetch origin
git checkout epic/{parent.number}-{kebab-title}   # or main
git pull
```

---

## Step 3 — Create feature branch

```bash
git checkout -b type/short-description epic/{parent.number}-{kebab-title}
# or: git checkout -b type/short-description main
```

Branch naming: `type/scope-description` (e.g. `feat/tasks-create-endpoint`).

Claim the issue immediately so other loop iterations skip it:

```bash
gh issue edit <number> --add-label "in-progress" --remove-label "ready-for-agent"
```

---

## Step 4 — Implement

Read `frontend/CONTEXT.md` and `api/CONTEXT.md` before touching those layers.

Key rules:
- Frontend: features live in `src/features/<name>/` — co-locate components, hooks, queries, types
- Frontend: workspace-scoped calls use `useWorkspaceApi()` — never raw fetch
- Frontend: query keys always include `workspaceId` as second element
- API: all workspace routes nested under `/api/workspaces/{workspaceId}/`
- API: UUIDs are UuidV7, PKs stored as BINARY(16)
- Multi-tenancy: data isolation enforced at model level, not just runtime checks
- No cross-feature imports — shared code goes to `lib/` or `types/`
- No comments unless WHY is non-obvious
- No error handling for impossible scenarios

---

## Step 5 — Test before every commit

Run tests matching what you touched:

```bash
# Frontend changed:
make front-test

# API changed:
make test

# Both changed:
make front-test && make test
```

Also run full quality check:

```bash
make check
```

**Do not commit if any check fails.** Fix first, then re-run.

---

## Step 6 — Commit

Conventional Commits format — scope required:

```
type(scope): description
```

Types: `feat`, `fix`, `chore`, `docs`, `refactor`, `test`, `ci`, `perf`  
Scopes: `auth`, `api`, `frontend`, `docker`, `db`, `rate-limiter`, `adr`, `hooks`, `ci`, `deps`

Use narrowest accurate scope. Lowercase imperative, no trailing period, subject ≤ 72 chars.

```bash
git add <specific files — never git add -A blindly>
git commit -m "feat(frontend): add task creation form"
```

---

## Step 7 — Open PR

```bash
gh pr create \
  --base epic/{parent.number}-{kebab-title} \
  --title "feat(scope): short description" \
  --body "$(cat <<'EOF'
Closes #ISSUE_NUMBER

## What
- bullet of what changed

## Why
- reason from issue

## Test plan
- [ ] `make front-test` passes
- [ ] `make test` passes
- [ ] `make check` passes
EOF
)"
```

---

## Step 8 — Confirm PR

Verify the PR URL printed by Step 7 is open. The issue closes automatically when the PR merges via `Closes #ISSUE_NUMBER` in the PR body — do not close it manually.

---

## Step 9 — Decide next action

Issues claimed in Step 3 no longer carry `ready-for-agent`, so the Step 1 query naturally skips them on the next iteration.

- More eligible `ready-for-agent` issues exist → loop continues (do NOT output `<promise>`)
- No more eligible issues → output exactly:

```
<promise>NO READY ISSUES</promise>
```

---

## Hard rules

1. **One issue per iteration** — never batch two issues in one loop turn
2. **Tests must pass before every commit** — no exceptions
3. **Never push to `main` or an epic branch directly** — always via PR
4. **Never `git add -A`** — stage specific files to avoid committing `.env` or generated artifacts
5. **Never skip hooks** (`--no-verify` forbidden)
6. **Skip any issue labelled `v2`** — out of scope for this loop
7. **Stop and output `needs input:` on your own line** if: migration conflict, auth decision, ambiguous spec, missing env var
