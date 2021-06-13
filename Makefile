CONSOLE=$(SYMFONY) console
DOCKER_COMPOSE?=docker-compose
RUN?=$(SYMFONY) run
SYMFONY?=symfony

.DEFAULT_GOAL := help
.PHONY: help
.PHONY: start stop install uninstall
.PHONY: db-reset db-fixtures
.PHONY: assets-server assets-watch assets-dev assets-build assets-analyzer
.PHONY: tests tests-weak tets-all test-all-weak lint lint-symfony lint-yaml lint-twig lint-container php-cs security-check validate-schema
.PHONY: deps

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## Project setup
##---------------------------------------------------------------------------

start:                                                                                                 ## Start project
	$(SYMFONY) proxy:start
	$(DOCKER_COMPOSE) start
	$(SYMFONY) server:start -d

stop:                                                                                                  ## Stop project
	$(DOCKER_COMPOSE) stop
	$(SYMFONY) server:stop

install:                                                                                               ## Install the project
	$(DOCKER_COMPOSE) up -d --remove-orphans
	$(MAKE) deps
	$(MAKE) db-fixtures

uninstall:                                                                                             ## Uninstall the project
	$(DOCKER_COMPOSE) down
	rm -R node_modules/  var/ vendor/

##
## Database
##---------------------------------------------------------------------------

db-reset:                                                                                              ## Reset the database
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate -n

db-fixtures: db-reset                                                                                  ## Apply doctrine fixtures
	$(CONSOLE) doctrine:fixtures:load -n


##
## Assets
##---------------------------------------------------------------------------

assets-server: node_modules                                                                            ## Run assets server
	yarn dev-server

assets-watch: node_modules                                                                             ## Watch the assets and build their development version on change
	yarn watch

assets-dev: node_modules                                                                               ## Build the development version of the assets
	yarn dev

assets-build: node_modules                                                                             ## Build the production version of the assets
	yarn build

assets-analyze:                                                                                       ## Analyze generated assets files
	yarn run --silent build --json > webpack-stats.json
	yarn webpack-bundle-analyzer webpack-stats.json public/build


##
## Tests
##---------------------------------------------------------------------------

tests:                                                                                                ## Run all the PHP tests
	$(CONSOLE) cache:clear --env test
	FOUNDRY_RESET_MODE=migrate $(RUN) php bin/phpunit

tests-weak:                                                                                           ## Run all the PHP tests without Deprecations helper
	$(CONSOLE) cache:clear --env test
	SYMFONY_DEPRECATIONS_HELPER=weak FOUNDRY_RESET_MODE=migrate $(RUN) php bin/phpunit

test-all: lint validate-schema security-check tests                                                    ## Lint all, check vulnerable dependencies, run PHP tests

test-all-weak: lint validate-schema security-check tests-weak                                          ## Lint all, check vulnerable dependencies, run PHP tests without Deprecations helper

lint: lint-symfony php-cs                                                                              ## Run lint on Twig, YAML, PHP and Javascript files

lint-symfony: lint-yaml lint-twig lint-container                                                       ## Lint Symfony (Twig and YAML) files

lint-yaml:                                                                                             ## Lint YAML files
	$(CONSOLE) lint:yaml --parse-tags config

lint-twig:                                                                                             ## Lint Twig files
	$(CONSOLE) lint:twig templates

lint-container:                                                                                        ## Lint Symfony Container
	$(CONSOLE) lint:container

php-cs:                                                                                                ## Lint PHP code
	$(SYMFONY) php vendor/bin/php-cs-fixer fix --dry-run --diff --no-interaction -v

security-check:                                                                                        ## Check for vulnerable dependencies
	$(SYMFONY) security:check

validate-schema:                                                                                       ## Test the doctrine Schema
	$(CONSOLE) doctrine:schema:validate --skip-sync -vvv --no-interaction


##
## Dependencies
##---------------------------------------------------------------------------

deps: vendor assets-dev                                                                                ## Install the project dependencies


##


# Rules from files

vendor: composer.lock
	composer install -n

composer.lock: composer.json
	@echo compose.lock is not up to date.

node_modules: yarn.lock
	yarn install

yarn.lock: package.json
	@echo yarn.lock is not up to date.
