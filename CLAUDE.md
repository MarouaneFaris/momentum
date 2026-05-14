# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Monorepo with two apps served via Docker:

- `api/` — Symfony 8.0 (PHP 8.5+) REST API, served by FrankenPHP
- `frontend/` — React 19 + TypeScript + Vite, port 3000
- `docker/` — Docker Compose config; dev overrides in `compose.override.yaml`

Database: MariaDB. Planned but not yet wired: Redis (cache), RabbitMQ (queues).

## Development

All commands go through `make`. Run `make help` for full list.

```bash
make install      # First-time setup: rebuild images + start containers
make up           # Start containers (detached)
make down         # Stop containers
make sh           # Shell into PHP container
make bash         # Bash into PHP container (arrow key history)
make logs         # Tail logs
```

Run Symfony console commands:
```bash
make sf c="route:list"
make cc           # cache:clear
```

Database:
```bash
make migrate-db   # Run pending migrations
make reset-db     # Drop → create → migrate
```

## Quality Checks

```bash
make check        # All checks: frontend + backend
make back-check   # PHPStan (level 6) + php-cs-fixer check
make back-cs-fix  # Auto-fix PHP code style
make front-check  # tsc --noEmit + ESLint + Prettier check
```

CI runs `make check` equivalent via GitHub Actions (`.github/workflows/quality.yaml`).

## API

All routes under `/api/`. Public: `/api/login`, `/api/register`. Everything else requires `ROLE_USER`.

Auth is **stateful token-based** (stored in DB), delivered via cookies with HttpOnly + SameSite + Secure flags. JSON login uses `email` + `password` fields.

Controllers live in `src/Controller/Api/`. Entities in `src/Entity/`, repositories in `src/Repository/`.

## Domain Decisions (from ADRs)

**Multi-tenancy**: SaaS — anyone can register, create workspaces, become owner. Data isolation between tenants is critical and must be enforced at the data model level, not just runtime checks. Every new feature must be reviewed for data isolation.

**RBAC**: Roles are **workspace-scoped** (not per-user). Three roles: `owner`, `member`, `guest`. Permissions are additive (no negation). A user can hold different roles across different workspaces.

**Authentication**: Stateful tokens preferred over JWT because tokens must be revocable (JWT can't be revoked without infrastructure). Cookie storage preferred over localStorage (XSS resistance).

## Frontend

`@` alias resolves to `frontend/src/`. HTTPS enabled in dev via `@vitejs/plugin-basic-ssl`. Husky + lint-staged runs on commit (ESLint + Prettier).

Planned packages not yet installed: ReactRouter, ReactQuery.

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (60-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk go test             # Go test failures only (90%)
rtk jest                # Jest failures only (99.5%)
rtk vitest              # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk pytest              # Python test failures only (90%)
rtk rake test           # Ruby test failures only (90%)
rtk rspec               # RSpec test failures only (60%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->