# Executables (local)
ENV_FILE_ARGS = --env-file .env $(if $(wildcard .env.local),--env-file .env.local,)
COMPOSE = DOCKER_UID=$(shell id -u) DOCKER_GID=$(shell id -g) docker compose $(ENV_FILE_ARGS)
DOCKER_COMP = $(COMPOSE) -f docker/compose.yaml -f docker/compose.override.yaml

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php
NODE_CONT = $(DOCKER_COMP) exec frontend

# Executables
PHP = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY = $(PHP) bin/console
SYMFONY_TEST = $(SYMFONY) -e test
PNPM = $(NODE_CONT) corepack pnpm

# Frontend
FRONTEND_DIR = frontend

# Functions
with-files = $(if $(f),$(1),$(2))

# Misc
MAKEFLAGS += --no-print-directory
.DEFAULT_GOAL = help
.PHONY : help \
	build rebuild up down nuke logs config sh bash node-sh \
	test test-unit test-integration test-functional \
	composer vendor \
	sf cc cc-test flush-redis create-db drop-db migrate-db reset-db load-fixtures \
	create-db-test drop-db-test migrate-db-test reset-db-test reset-dbs \
	back-cs-fix stan back-check \
	pnpm front-install front-lint front-lint-fix front-format front-check front-test \
	check \
	install-hooks dev-certs install

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build

rebuild: ## Rebuilds the Docker images without cache
	@$(DOCKER_COMP) build --pull --no-cache

up: down ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

nuke: ## Stop the docker hub and remove volumes
	@$(DOCKER_COMP) down --remove-orphans -v

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

config: ## Dump docker compose config
	@$(DOCKER_COMP) config

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

node-sh: ## Connect to the Nodejs container
	@$(NODE_CONT) sh

test: ## Run PHPUnit inside the PHP container, pass c= for extra options, example: make test c="--testsuite Unit"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

test-unit: ## Run only the Unit test suite
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --testsuite Unit

test-integration: ## Run only the Integration test suite
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --testsuite Integration

test-functional: ## Run only the Functional test suite
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --testsuite Functional

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

cc-test: ## Clear the cache (env=test)
	@$(SYMFONY_TEST) c:c

flush-redis: c=cache:pool:clear cache.rate_limiter ## Clear rate limiter data in Redis
flush-redis: sf

create-db: c=doctrine:database:create --if-not-exists ## Create the database if it doesn't exists
create-db: sf

drop-db: c=doctrine:database:drop --force --if-exists ## Drop the database if it exists
drop-db: sf

migrate-db: c=doctrine:migrations:migrate --no-interaction ## Update the database up to the last migration
migrate-db: sf

load-fixtures: c=doctrine:fixtures:load --no-interaction ## Load dev fixtures
load-fixtures: sf

reset-db: ## Drop database, re-create it, run migrations, and load fixtures
	@$(MAKE) drop-db
	@$(MAKE) create-db
	@$(MAKE) migrate-db
	@$(MAKE) load-fixtures

create-db-test: ## Create the test database if it doesn't exist
	@$(SYMFONY_TEST) doctrine:database:create --if-not-exists

drop-db-test: ## Drop the test database if it exists
	@$(SYMFONY_TEST) doctrine:database:drop --force --if-exists

migrate-db-test: ## Run migrations on the test database
	@$(SYMFONY_TEST) doctrine:migrations:migrate --no-interaction

reset-db-test: ## Drop test database, re-create it, then run migrations
	@$(MAKE) drop-db-test
	@$(MAKE) create-db-test
	@$(MAKE) migrate-db-test

reset-dbs: reset-db reset-db-test flush-redis

## —— Backend 🐘 ———————————————————————————————————————————————————————————————
back-cs-fix: ## Fix PHP code style (pass f="file1 file2" to target specific files)
	@$(call with-files,$(PHP_CONT) vendor/bin/php-cs-fixer fix $(f),$(COMPOSER) cs-fix)

stan: ## Run PHPStan static analysis
	@$(COMPOSER) phpstan

back-check: ## Run all backend quality checks (phpstan + cs)
	@$(COMPOSER) check

## —— Frontend 🌐 ——————————————————————————————————————————————————————————————
pnpm: ## Run pnpm, pass the parameter "c=" to run a given command, example: make pnpm c='add zod'
	@$(eval c ?=)
	@$(PNPM) $(c)

front-install: ## Install frontend dependencies
	@$(PNPM) install

front-lint: ## Lint frontend code
	@$(PNPM) run lint

front-lint-fix: ## Lint and auto-fix frontend code (pass f="file1 file2" to target specific files)
	@$(call with-files,$(NODE_CONT) pnpm exec eslint --fix $(f),$(PNPM) run lint:fix)

front-format: ## Format frontend code with Prettier (pass f="file1 file2" to target specific files)
	@$(call with-files,$(NODE_CONT) pnpm exec prettier --write $(f),$(PNPM) run format)

front-test: ## Run frontend tests
	@$(PNPM) run test:run

front-check: ## Run all frontend quality checks (type-check + lint + format)
	@$(PNPM) run check

## —— Quality ✅ ———————————————————————————————————————————————————————————————
check: front-check back-check ## Run all quality checks

## —— Global 🌍 ———————————————————————————————————————————————————————————————
install-hooks: ## Configure git to use scripts/hooks as the hooks directory
	@git config core.hooksPath scripts/hooks
	@chmod +x scripts/hooks/pre-commit scripts/hooks/pre-push scripts/hooks/commit-msg
	@echo "Git hooks installed."

dev-certs: ## Generate trusted local HTTPS certs with mkcert (run once, requires mkcert)
	@which mkcert > /dev/null 2>&1 || (echo "mkcert not found. Install: sudo apt install mkcert  (or brew install mkcert)" && exit 1)
	@mkdir -p docker/certs
	mkcert -install
	mkcert -cert-file docker/certs/tls.pem -key-file docker/certs/tls.key localhost 127.0.0.1 ::1
	@echo "Done. Run: make up"

install: dev-certs rebuild ## Install the whole project for the first time
	@$(DOCKER_COMP) down --remove-orphans
	@$(DOCKER_COMP) up --detach --wait
	@$(MAKE) vendor
	@$(MAKE) reset-dbs
	@$(MAKE) install-hooks
