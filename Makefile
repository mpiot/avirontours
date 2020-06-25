CONSOLE=$(SYMFONY) console
DOCKER_COMPOSE?=docker-compose
PHPCSFIXER?=php-cs-fixer
RUN?=$(SYMFONY) run
SYMFONY?=symfony

.DEFAULT_GOAL := help
.PHONY: help
.PHONY: db-reset db-fixtures
.PHONY: tests

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## Project setup
##---------------------------------------------------------------------------

start:                                                                                                 ## Start project
	$(DOCKER_COMPOSE) start
	$(SYMFONY) server:start -d

stop:                                                                                                  ## Stop project
	$(DOCKER_COMPOSE) stop
	$(SYMFONY) server:stop

install:                                                                                               ## Install the project
	$(DOCKER_COMPOSE) up -d --remove-orphans
	$(MAKE) deps
	$(MAKE) db-fixtures


##
## Database
##---------------------------------------------------------------------------

db-reset:                                                                                              ## Reset the database
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate -n

db-fixtures: db-reset                                                                                 ## Apply doctrine fixtures
	$(CONSOLE) doctrine:fixtures:load -n


##
## Assets
##---------------------------------------------------------------------------

assets-watch: node_modules                                                                                    ## Watch the assets and build their development version on change
	yarn watch

assets: node_modules                                                                                   ## Build the development version of the assets
	yarn dev

##
## Tests
##---------------------------------------------------------------------------

tests: db-fixtures                                                                                     ## Run all the PHP tests
	$(RUN) php bin/phpunit

tests-weak: db-fixtures                                                                                ## Run all the PHP tests without Deprecations helper
	SYMFONY_DEPRECATIONS_HELPER=weak $(RUN) php bin/phpunit

test-all: lint test-schema security-check tests                                                        ## Lint all, check vulnerable dependencies, run PHP tests

test-all-weak: lint test-schema security-check tests-weak                                              ## Lint all, check vulnerable dependencies, run PHP tests without Deprecations helper

lint: lint-symfony php-cs                                                                              ## Run lint on Twig, YAML, PHP and Javascript files

lint-symfony: lint-yaml lint-twig                                                                      ## Lint Symfony (Twig and YAML) files

lint-yaml:                                                                                             ## Lint YAML files
	$(CONSOLE) lint:yaml --parse-tags config

lint-twig:                                                                                             ## Lint Twig files
	$(CONSOLE) lint:twig templates

php-cs:                                                                                                ## Lint PHP code
	$(PHPCSFIXER) fix --diff --dry-run --no-interaction -v

security-check:                                                                                        ## Check for vulnerable dependencies
	$(SYMFONY) security:check

test-schema:                                                                                           ## Test the doctrine Schema
	$(CONSOLE) doctrine:schema:validate --skip-sync -vvv --no-interaction


##
## Dependencies
##---------------------------------------------------------------------------

deps: vendor assets                                                                                    ## Install the project dependencies


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
