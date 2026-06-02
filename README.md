<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/MarouaneFaris/momentum/main/frontend/public/logo-icon-dark.svg">
  <img alt="Momentum" src="https://raw.githubusercontent.com/MarouaneFaris/momentum/main/frontend/public/logo-icon-light.svg" height="48">
</picture>

# Momentum

[![Quality checks](https://img.shields.io/github/actions/workflow/status/MarouaneFaris/momentum/quality.yaml?branch=main&label=Quality+checks)](https://github.com/MarouaneFaris/momentum/actions/workflows/quality.yaml)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)
![Node](https://img.shields.io/badge/Node-24_LTS-339933?logo=node.js)

## Stack

| Layer    | Tech                             |
| -------- | -------------------------------- |
| API      | Symfony 8.0, PHP 8.5, FrankenPHP |
| Frontend | React 19, TypeScript, Vite       |
| Database | MariaDB LTS                      |
| Runtime  | Docker Compose                   |

## Prerequisites

- Docker + Docker Compose
- `make`

## Setup

```bash
make install   # build images + start containers + install git hooks (that's it!)
```

## Daily Commands

```bash
make up        # start containers (detached)
make down      # stop containers
make logs      # tail logs
make bash      # bash into PHP container
make sh        # sh into PHP container
```

## Symfony

```bash
make sf c="route:list"   # run any console command
make cc                  # cache:clear
```

## Database

```bash
make migrate-db   # run pending migrations
make reset-db     # drop → create → migrate
```

## Quality

```bash
make check          # all checks (frontend + backend)
make back-check     # PHPStan + php-cs-fixer check
make back-cs-fix    # auto-fix PHP code style
make front-check    # tsc + ESLint + Prettier check
make front-lint-fix # auto-fix frontend lint
make front-format   # auto-format with Prettier
```

Quality checks are run on every commit with git hooks.
Phpstan and database schema validation are run on every push via git hooks.
CI runs quality checks on every pull request targetting main via GitHub Actions.

## Ports (dev)

| Service  | URL                    |
| -------- | ---------------------- |
| API      | https://localhost      |
| Frontend | https://localhost:3000 |
| Database | localhost:3306         |
