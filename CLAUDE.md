# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

**Aviron Tours Métropole** management app — a Symfony 8.1 / PHP 8.5 web application for a rowing club: members & licenses, seasons, boats ("shells") & their damage tracking, an outings logbook ("cahier des sorties"), trainings (with Concept2 ergometer sync), sport profiles, and physiological measures. User-facing strings are in **French**.

Stack: Symfony full-stack (Twig + Hotwired Stimulus/Turbo via Webpack Encore), PostgreSQL, Doctrine ORM. Frontend served over the Symfony local server; PostgreSQL and Mailpit run in Docker.

## Commands

PHP runs through the Symfony CLI (`symfony php`, `symfony console`), which injects env vars from the Docker services. `make help` lists everything; the targets that matter when changing code:

- `make tests` — the full pre-PR gate: `lint` + `doctrine:schema:validate` + `phpunit`. `lint` covers composer validate, yaml/twig/container lint, rector dry-run, php-cs-fixer dry-run, phpstan, eslint. (The Makefile runs phpunit with `--do-not-fail-on-deprecation`.)
- Run a **single test**: `symfony php vendor/bin/phpunit --filter TestMethodName` or by path, e.g. `symfony php vendor/bin/phpunit tests/Controller/TrainingControllerTest.php`. (CI uses `bin/phpunit`.)
- When lint fails, run the auto-fixers — don't hand-tweak style: `symfony php vendor/bin/php-cs-fixer fix` and `symfony php vendor/bin/rector process`.
- Frontend: `npm run build` (Encore) must run before the test suite — controllers reference the Encore manifest; `npm run lint` is ESLint over `assets` + `bin`.

## Non-obvious conventions (CI will fail otherwise)

- **Every PHP file** must start with `declare(strict_types=1);` and the Apache-2.0 file header comment (`Copyright 2020 Mathieu Piot`). Enforced by php-cs-fixer's `header_comment` + `declare_strict_types`. Copy the header from any existing file in `src/`.
- Ruleset is `@Symfony` + `@Symfony:risky` plus strict comparisons (`strict_comparison`, `strict_param`), `mb_str_functions`, ordered imports/class elements. PHPStan runs at **level 5** with Doctrine/Symfony/strict/deprecation extensions.
- Rector and php-cs-fixer both gate the PR — run their fixers, don't hand-tweak style.

## Architecture

Standard Symfony layout: `App\` → `src/`, `App\Tests\` → `tests/`. Controllers use PHP 8 attributes throughout (`#[Route]`, `#[IsGranted]`, `#[MapEntity]`, `#[MapQueryParameter]`) — no YAML/annotation routing.

### Security & multi-firewall / subdomain design
`config/packages/security.yaml` defines **two firewalls**:
- `main` — app users (`App\Entity\User`), form login via `App\Security\LoginFormAuthenticator`, **email-based 2FA** (scheb/2fa), remember-me, and `switch_user` impersonation.
- `logbook` — bound to a **separate host** (`%logbook_host%`, from `DEFAULT_LOGBOOK_URI`) and the `/logbook-entry` path, using HTTP Basic against an in-memory `logbook` user. This is the public tablet-facing outings logbook on its own subdomain. Some routes also pin a `host:` (e.g. Concept2 OAuth on `my.avirontours.fr`).

**Role hierarchy** (grant admin sub-areas, not a single ROLE_ADMIN):
`ROLE_SUPER_ADMIN` → `ROLE_ADMIN` → { `ROLE_LOGBOOK_ADMIN`, `ROLE_MATERIAL_ADMIN`, `ROLE_SPORT_ADMIN`, `ROLE_USER_ADMIN`, `ROLE_SEASON_ADMIN` }; `ROLE_SEASON_ADMIN` → { `ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN`, `ROLE_SEASON_PAYMENTS_ADMIN` }. Admin controllers live in `src/Controller/Admin/` and each guards on its specific role.

Non-admin feature access is commonly gated by `is_granted("VALID_LICENSE")` (sometimes OR-ed with `ROLE_ADMIN` in an `Expression`). The `App\Security\Voter\ValidLicenseVoter` backs that attribute, delegating to `LicenseRepository::hasValidLicenseForActiveSeason()` (a single COUNT query, memoised per request) — check there when reasoning about member-facing pages.

### License validation workflow
`config/packages/workflow.yaml` defines a **Symfony Workflow** (`license`) over `App\Entity\License` with a dual initial marking (`wait_medical_certificate_validation`, `wait_payment_validation`). Transitions (validate/reject medical certificate, validate payment, validate license) are guarded by the season admin roles above. Audit trail is enabled. Treat license state changes as workflow transitions, not direct property writes.

- **New licenses must be seeded with the initial marking at creation**: call `$licenseWorkflow->getMarking($license)` before persist/flush (the marking doesn't self-populate — see `LicenseController::new`). Licenses are always stored with an explicit marking, never `[]`.
- `License::$marking` is a PostgreSQL **`json` column, which has no `=` operator** — never compare it directly in SQL. Query license state through the custom `JSON_GET_FIELD_AS_TEXT` DQL function (custom AST functions in `src/Doctrine/ORM/Query/AST/`, registered under `dql.string_functions` in `config/packages/doctrine.yaml`); `LicenseRepository` filters all state that way. For a whole-value comparison in raw SQL (e.g. a migration), cast with `marking::jsonb`.

### Concept2 ergometer integration
Trainings can be imported from Concept2 (`log.concept2.com`): OAuth via `src/OAuth/Concept2Provider.php` + `Concept2OauthController`, API calls in `src/Service/Concept2ApiConsumer.php`, and the import runs **asynchronously** through Symfony Messenger — `Concept2ImportMessage` → `Concept2ImportMessageHandler`. Messenger transport is Doctrine (`MESSENGER_TRANSPORT_DSN=doctrine://default`).

### Other services worth knowing
`src/Service/`: `FileUploader` (uploads via oneup/flysystem-bundle), `PdfGenerator` (uses Puppeteer via `bin/html-print.mjs`), `SeasonCsvGenerator`, `TrainingCalculator`/`TrainingHelper`, `ShellAbbreviationGenerator`. Charts in `src/Chart/` (symfony/ux-chartjs). Twig extension in `src/Twig/Extension/`.

## Testing

Functional tests extend `App\Tests\AppWebTestCase` (`tests/`), which mixes in Foundry's `Factories` + `ResetDatabase`. Key patterns:
- Build entities with **Zenstruck Foundry factories** (`src/Factory/*Factory.php`), not manual construction. Log a user with `$this->createAndLogin($client, 'ROLE_...')`.
- **DAMA DoctrineTestBundle** wraps each test in a transaction (rolled back) — no manual DB cleanup.
- PHPUnit is strict: `failOnDeprecation`/`failOnNotice`/`failOnWarning` are on. Symfony clock-mock and dns-mock are enabled for the `App` namespace.
- Same `*Factory` classes back both tests and `src/DataFixtures/`.

## Config notes

- Env: `.env` (committed defaults) + `.env.local` / `.env.test`. Secrets via `config/secrets/`.
- Local mail is caught by **Mailpit** (`compose.override.yaml`) instead of being sent.
- Deployment is **Ansible** — `deploy.yaml` / `rollback.yaml` (playbooks) + `hosts.yaml` (inventory: prod host `rhea.avirontours.fr`, `git_version: develop`). It rsyncs a release, runs migrations, and reloads PHP-FPM + Messenger workers. Release notes/version in `changelog.json` + `APP_VERSION`.
