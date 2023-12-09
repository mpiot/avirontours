# Executables (local)
DOCKER_COMPOSE = docker compose -f compose.yaml -f compose.override.yaml

# Executables
SYMFONY  = symfony
PHP      = $(SYMFONY) php
COMPOSER = $(SYMFONY) composer
CONSOLE  = $(SYMFONY) console
NPM      = npm

# Misc
.DEFAULT_GOAL : help
.PHONY        : help
.PHONY        : start stop restart
.PHONY        : docker-start docker-stop docker-up docker-down docker-logs
.PHONY        : db-reset db-fixtures
.PHONY        : tests lint validate-schema phpunit

# Help display
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'


##
## Project 🧙
##-------------------------------------------------------------------------------
start: docker-start ## Start Project
	@$(SYMFONY) proxy:start
	@$(SYMFONY) server:start -d

stop: docker-stop ## Stop Project
	@$(SYMFONY) server:stop
	@$(SYMFONY) proxy:stop

restart: stop start ## Restart Project


##
## Docker 🐳
##-------------------------------------------------------------------------------
docker-start: ## Start services
	@$(DOCKER_COMPOSE) start

docker-stop: ## Stop services
	@$(DOCKER_COMPOSE) stop

docker-up: ## Create and start containers
	@$(DOCKER_COMPOSE) up --detach

docker-down: ## Stop and remove resources
	@$(DOCKER_COMPOSE) down -v --remove-orphans

docker-logs: ## View output from containers
	@$(DOCKER_COMPOSE) logs --tail=0 --follow


##
## Database 🛢️
##---------------------------------------------------------------------------
db-reset: ## Reset the database
	@$(CONSOLE) doctrine:database:drop --force --if-exists
	@$(CONSOLE) doctrine:database:create --if-not-exists
	@$(CONSOLE) doctrine:migrations:migrate -n

db-fixtures: db-reset ## Reset the database, then apply doctrine fixtures
	rm -Rf protected_files
	@$(CONSOLE) doctrine:fixtures:load -n


##
## Tests 🚦️
##---------------------------------------------------------------------------
tests: lint validate-schema phpunit ## Lint all, run PHP tests

lint: ## Run lint on Yaml, Twig, Container, and PHP files
	@$(COMPOSER) validate
	@$(CONSOLE) lint:yaml --parse-tags config
	@$(CONSOLE) lint:twig templates --env=prod
	@$(CONSOLE) lint:container
	@$(PHP) vendor/bin/rector process --dry-run
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --no-interaction -v
	@$(PHP) vendor/bin/phpstan
	@${NPM} run lint

validate-schema: ## Test the doctrine schema
	@$(CONSOLE) doctrine:schema:validate

phpunit: ## Run tests
	@FOUNDRY_RESET_MODE=migrate $(PHP) vendor/bin/phpunit
