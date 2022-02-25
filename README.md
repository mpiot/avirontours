<h1 align="center"><img src="assets/images/logo_colored.png" alt="Aviron Tours"></h1>

[![Lint](https://github.com/mpiot/avirontours/actions/workflows/lint.yaml/badge.svg)](https://github.com/mpiot/avirontours/actions/workflows/lint.yaml)
[![Tests](https://github.com/mpiot/avirontours/actions/workflows/tests.yaml/badge.svg)](https://github.com/mpiot/avirontours/actions/workflows/tests.yaml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mpiot_avirontours&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=mpiot_avirontours)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=mpiot_avirontours&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=mpiot_avirontours)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=mpiot_avirontours&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=mpiot_avirontours)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=mpiot_avirontours&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=mpiot_avirontours)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=mpiot_avirontours&metric=coverage)](https://sonarcloud.io/summary/new_code?id=mpiot_avirontours)


### Summary
1. [Install](#install)
    1. [Install Docker and docker-compose](#install-docker-and-docker-compose)
    2. [Install the application](#install-the-application)
    3. [SSL configuration](#ssl-configuration)
2. [Follow the best practice](#follow-the-best-practice)
3. [How to control your code before open a pull request ?](#how-to-control-your-code-before-open-a-pull-request-)
    1. [Code Syntax](#code-syntax)
    2. [Run tests](#run-tests)
4. [Shortcuts](#shortcuts)

## Install

### Install Docker and docker-compose
The app use docker and docker-compose, before continue to follow the guide, please install these requirements.
* https://docs.docker.com/install/
* https://docs.docker.com/compose/install/

There is a requirement when running Elasticsearch in Docker:
https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html#_set_vm_max_map_count_to_at_least_262144

### Install the application
```shell
make build
make up
make open
```

And voilà !!! Your app is installed and ready to use.

### SSL configuration
To avoid TLS trust issues, copy the self-signed certificate from Caddy and add it to the trusted certificates :
```ssh
# Mac
docker cp $(docker-compose ps -q caddy):/data/caddy/pki/authorities/local/root.crt /tmp/root.crt && sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/root.crt
# Linux
docker cp $(docker-compose ps -q caddy):/data/caddy/pki/authorities/local/root.crt /usr/local/share/ca-certificates/root.crt && sudo update-ca-certificates
# Windows
docker cp $(docker-compose ps -q caddy):/data/caddy/pki/authorities/local/root.crt .
# Add the certificate in Computer certificates/Trusted Root Certification Athorities
```

## Follow the best practice
There is a **beautiful** guide about the best practice :) You can find it on the [Symfony Documentation - Best Practice](http://symfony.com/doc/current/best_practices/index.html).

## How to control your code before open a pull request ?

### Code Syntax
For a better structure of the code, we use Coding standards: PSR-0, PSR-1, PSR-2 and PSR-4.
You can found some informations on [the synfony documentation page](http://symfony.com/doc/current/contributing/code/standards.html).

In the project you have a php-cs-fixer.phar file, [the program's documentation](http://cs.sensiolabs.org/).

```shell
make lint
```


### Run tests

You can run a set of tests: testing, linting, security check, database sync check, etc... by running a single command:
```shell
make tests
```

## Shortcuts

Some shortcuts are provided from a Makefile, you can list them by running:
```shell
make help
```

## Project dependencies

The project use many dependencies:

- Server side
  - PHP
  - Postgresql
- Back side
  - Symfony
- Front side
  - Twig
  - Stimulus (hotwired)
  - Turbo (hotwired)
