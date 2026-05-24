# Executables (local)
COMPOSE = UID=$(shell id -u) GID=$(shell id -g) docker compose
DOCKER_COMP = $(COMPOSE) -f docker/compose.yaml -f docker/compose.override.yaml

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php
NODE_CONT = $(DOCKER_COMP) exec frontend

# Executables
PHP = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY = $(PHP) bin/console
NPM = $(NODE_CONT) npm

# Frontend
FRONTEND_DIR = frontend

# Functions
with-files = $(if $(f),$(1),$(2))

# Misc
MAKEFLAGS += --no-print-directory
.DEFAULT_GOAL = help
.PHONY : help build up start down logs sh composer vendor sf cc test install-hooks dev-certs

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

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

node-sh: ## Connect to the Nodejs container
	@$(NODE_CONT) sh

test: ## Run PHPUnit inside the PHP container, pass c= for extra options, example: make test c="--testsuite Unit"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

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

flush-redis: c=cache:pool:clear cache.rate_limiter ## Clear rate limiter data in Redis
flush-redis: sf

create-db: c=doctrine:database:create --if-not-exists ## Create the database if it doesn't exists
create-db: sf

drop-db: c=doctrine:database:drop --force --if-exists ## Drop the database if it exists
drop-db: sf

migrate-db: c=doctrine:migrations:migrate --no-interaction ## Update the database up to the last migration
migrate-db: sf

reset-db: ## Drop database, then re-create it, then run migrations
	@$(MAKE) drop-db
	@$(MAKE) create-db
	@$(MAKE) migrate-db

## —— Backend 🐘 ———————————————————————————————————————————————————————————————
back-cs-fix: ## Fix PHP code style (pass f="file1 file2" to target specific files)
	@$(call with-files,$(PHP_CONT) vendor/bin/php-cs-fixer fix $(f),$(COMPOSER) cs-fix)

phpstan: ## Run PHPStan static analysis
	@$(COMPOSER) phpstan

back-check: ## Run all backend quality checks (phpstan + cs)
	@$(COMPOSER) check

## —— Frontend 🌐 ——————————————————————————————————————————————————————————————
front-install: ## Install frontend dependencies
	@$(NPM) install

front-lint: ## Lint frontend code
	@$(NPM) run lint

front-lint-fix: ## Lint and auto-fix frontend code (pass f="file1 file2" to target specific files)
	@$(call with-files,$(NODE_CONT) npx eslint --fix $(f),$(NPM) run lint:fix)

front-format: ## Format frontend code with Prettier (pass f="file1 file2" to target specific files)
	@$(call with-files,$(NODE_CONT) npx prettier --write $(f),$(NPM) run format)

front-check: ## Run all frontend quality checks (type-check + lint + format)
	@$(NPM) run check

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

install: dev-certs rebuild up install-hooks ## Install the whole project for the first time
