# Triage Labels

The skills speak in terms of five canonical triage roles. This file maps those roles to the actual label strings used in this repo's issue tracker.

| Label in mattpocock/skills | Label in our tracker | Meaning                                  |
| -------------------------- | -------------------- | ---------------------------------------- |
| `needs-triage`             | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`               | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent`          | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human`          | `ready-for-human`    | Requires human implementation            |
| `wontfix`                  | `wontfix`            | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the corresponding label string from this table.

Edit the right-hand column to match whatever vocabulary you actually use.

## PRD / Epic labels

PRD and epic issues are **documentation only** — they are never directly implemented. They get broken into sub-issues instead.

### Type label

| Label | Meaning |
| ----- | ------- |
| `prd` | Issue is a PRD / epic doc. Never implement directly. |

**Rule**: Issues with `prd` MUST NOT receive `ready-for-agent` or `ready-for-human`. If you see those on a `prd` issue, remove them.

### State machine (exclusive — one at a time)

| Label | Meaning |
| ----- | ------- |
| `prd:drafting` | Being written; not yet actionable |
| `prd:ready` | Finalized; ready to be broken into sub-issues |
| `prd:active` | Sub-issues created; work ongoing |
| `prd:complete` | All sub-issues done; epic branch merged to main |

Transitions: `prd:drafting` → `prd:ready` → `prd:active` → `prd:complete`

## Creating labels in GitHub

These labels don't exist yet. Create them with:

```bash
gh label create needs-triage --color "#e4e669" --description "Maintainer needs to evaluate"
gh label create needs-info --color "#d93f0b" --description "Waiting on reporter for more information"
gh label create ready-for-agent --color "#0075ca" --description "Fully specified, ready for an AFK agent"
gh label create ready-for-human --color "#008672" --description "Requires human implementation"
gh label create wontfix --color "#ffffff" --description "Will not be actioned"
gh label create prd --color "#5319e7" --description "PRD / epic doc — never implement directly"
gh label create prd:drafting --color "#bfd4f2" --description "PRD being written"
gh label create prd:ready --color "#0075ca" --description "PRD finalized, ready to spawn sub-issues"
gh label create prd:active --color "#e4e669" --description "Sub-issues created, work ongoing"
gh label create prd:complete --color "#0e8a16" --description "All sub-issues done, epic merged"
```
