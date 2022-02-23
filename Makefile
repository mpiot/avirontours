# Executables (local)
DOCKER_COMPOSE = docker-compose

# Docker containers
PHP_CONTAINER = $(DOCKER_COMPOSE) exec $(EXTRA_OPTIONS) php

# Executables
PHP      = $(PHP_CONTAINER) php
PHPUNIT  = $(PHP_CONTAINER) bin/phpunit
COMPOSER = $(PHP_CONTAINER) composer
CONSOLE  = $(PHP_CONTAINER) bin/console
YARN     = $(PHP_CONTAINER) yarn

# Arguments
SERVER_NAME = avirontours.localhost

# Misc
.DEFAULT_GOAL = help
.PHONY        = help
.PHONY        = composer vendor
.PHONY        = db-reset db-fixtures
.PHONY        = start stop build up down logs sh open
.PHONY        = symfony
.PHONY        = yarn node-modules yarn-dev-server yarn-watch yarn-dev yarn-build yarn-analyze

# Help display
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'


##
## Composer 🧙
##-------------------------------------------------------------------------------
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer


##
## Database 🛢️
##---------------------------------------------------------------------------
db-reset: ## Reset the database
	@$(CONSOLE) doctrine:database:drop --force --if-exists
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@$(CONSOLE) doctrine:migrations:migrate -n

db-fixtures: db-reset ## Reset the database, then apply doctrine fixtures
	@$(CONSOLE) doctrine:fixtures:load -n


##
## Docker 🐳
##-------------------------------------------------------------------------------
start: ## Start project
	@SERVER_NAME=$(SERVER_NAME) $(DOCKER_COMPOSE) start

stop: ## Stop project
	@$(DOCKER_COMPOSE) stop

build: ## Builds the Docker images
	@$(DOCKER_COMPOSE) build --pull --no-cache

up: ## Up docker's containers in detached mode (no logs)
	@SERVER_NAME=$(SERVER_NAME) $(DOCKER_COMPOSE) up --detach

down: ## Remove containers
	@$(DOCKER_COMPOSE) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

sh: ## Connect to the PHP FPM container
	@$(PHP_CONTAINER) sh

open: ## Open the project in your favorite browser
	@xdg-open https://$(SERVER_NAME) >/dev/null 2>&1


##
## Symfony 🎵
##-------------------------------------------------------------------------------
symfony: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(CONSOLE) $(c)


##
## Tests 🚦️
##---------------------------------------------------------------------------
test: lint validate-schema ## Lint all, run PHP tests
	@$(CONSOLE) cache:clear --env test
	@$(DOCKER_COMPOSE) exec --env FOUNDRY_RESET_MODE=migrate php bin/phpunit

test-weak: lint validate-schema ## Lint all, run PHP tests without Deprecations helper
	@$(CONSOLE) cache:clear --env test
	@$(DOCKER_COMPOSE) exec --env SYMFONY_DEPRECATIONS_HELPER=weak --env FOUNDRY_RESET_MODE=migrate php bin/phpunit

lint: ## Run lint on Yaml, Twig, Container, and PHP files
	@$(CONSOLE) lint:yaml --parse-tags config
	@$(CONSOLE) lint:twig templates --env=prod
	@$(CONSOLE) lint:container
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --no-interaction -v
	@$(PHP) vendor/bin/psalm

validate-schema: ## Test the doctrine schema
	@$(CONSOLE) doctrine:schema:validate


##
## Yarn 🐈️
##---------------------------------------------------------------------------
yarn: ## Run yarn, pass the parameter "c=" to run a given command
	@$(eval c ?=)
	@$(YARN) $(c)

node-modules: ## Install node_modules according to the current yarn.lock file
node-modules: c=install
node-modules: yarn

yarn-dev-server: ## Run development server
	@$(YARN) dev-server

yarn-watch: ## Watch the assets and build their development version on change
	@$(YARN) watch

yarn-dev: ## Build the development version of the assets
	@$(YARN) dev

yarn-build: ## Build the production version of the assets
	@$(YARN) build

yarn-analyze: ## Analyze generated assets files
	@$(YARN) run --silent build --json > webpack-stats.json
	@$(YARN) webpack-bundle-analyzer webpack-stats.json public/build
