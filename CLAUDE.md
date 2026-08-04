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
- `ordered_class_elements` puts **static methods last**, after the public/protected/private instance methods — a new `public static` helper goes at the bottom of the class, not next to its instance caller.
- Prefer **string interpolation** (`"{$dir}/{$filename}"`) over `.` concatenation; extract a local when the expression is a long method chain. Not enforced by the fixers, so it will not be caught by `make tests`.
- `no_unused_imports` does **not** cover `use function` imports — remove those by hand when the last call goes away.

## Architecture

Standard Symfony layout: `App\` → `src/`, `App\Tests\` → `tests/`. Controllers use PHP 8 attributes throughout (`#[Route]`, `#[IsGranted]`, `#[MapEntity]`, `#[MapQueryParameter]`) — no YAML/annotation routing.

### Security & multi-firewall / subdomain design
`config/packages/security.yaml` defines **two firewalls**:
- `main` — app users (`App\Entity\User`), form login via `App\Security\LoginFormAuthenticator`, **email-based 2FA** (scheb/2fa), remember-me, and `switch_user` impersonation.
- `logbook` — bound to a **separate host** (`%logbook_host%`, from `DEFAULT_LOGBOOK_URI`) with **no `pattern:`**, so it covers every URL on that host, using HTTP Basic against an in-memory `logbook` user. This is the public tablet-facing outings logbook on its own subdomain. Some routes also pin a `host:` (e.g. Concept2 OAuth on `my.avirontours.fr`).

  Scoping the firewall by host alone is deliberate: with a `pattern:` the other URLs on that host would fall through to the `main` firewall and be served under *its* session, so a member logged in on the main site would reach them on the tablet. The whole host now answers under the `logbook` firewall instead, and the catch-all `- { path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }` rule is satisfied by Basic (`AuthenticatedVoter` grants `IS_AUTHENTICATED_REMEMBERED` to any full-fledged token).

  **`access_control` is first-match-wins, and its `PUBLIC_ACCESS` rules carry no `host:`** — so `- { host: '%logbook_host%', path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }` **must stay the first rule**. Without it `^/login$`, `^/register`, `^/reset-password`, `^/mentions-legales$`, `^/release-notes$` and `^/payment-attestation` match on the tablet host too and are served with no Basic challenge, leaving `App\EventSubscriber\LogbookHostFirewallSubscriber` as the sole control over the member-facing surface there. `testPublicPagesAreBasicAuthenticatedOnTheLogbookHost` pins it: drop the rule and those paths answer 302 instead of 401.

  The subscriber is still what shapes the host into a logbook: it redirects anything whose `_route` is not `logbook_entry_*` back to `logbook_entry_index`. Belt and braces — the firewall decides *who* gets in, the subscriber decides *what* is reachable once in.

  Its `kernel.request` **priority 0 is pinned explicitly**, and must stay there: `_route` is only set by `RouterListener` (priority 32) and the firewall answers at priority 8. Running earlier means `_route` is null, `u(null)->startsWith('logbook_entry_')` is `false`, and *every* request on the host redirects — `logbook_entry_index` included, i.e. an infinite loop that takes the tablet logbook offline. The dev paths it lets through (`^/(_profiler|_wdt|assets|build)/`) are a **hand-copied duplicate of the `dev` firewall's `pattern:`** in `security.yaml` — the two must be edited together. Add a path to the dev firewall alone and the subscriber 302s it to `logbook_entry_index` on the tablet host, killing the toolbar or the assets; drop one from `security.yaml` alone and it stays whitelisted past the redirect.

**Role hierarchy** (grant admin sub-areas, not a single ROLE_ADMIN):
`ROLE_SUPER_ADMIN` → `ROLE_ADMIN` → { `ROLE_LOGBOOK_ADMIN`, `ROLE_MATERIAL_ADMIN`, `ROLE_SPORT_ADMIN`, `ROLE_USER_ADMIN`, `ROLE_SEASON_ADMIN` }; `ROLE_SEASON_ADMIN` → { `ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN`, `ROLE_SEASON_PAYMENTS_ADMIN` }. Admin controllers live in `src/Controller/Admin/` and each guards on its specific role.

Non-admin feature access is commonly gated by `is_granted("VALID_LICENSE")` (sometimes OR-ed with `ROLE_ADMIN` in an `Expression`). The `App\Security\Voter\ValidLicenseVoter` backs that attribute, delegating to `LicenseRepository::hasValidLicenseForActiveSeason()` (a single COUNT query, memoised per request) — check there when reasoning about member-facing pages.

### License validation workflow
`config/packages/workflow.yaml` defines a **Symfony Workflow** (`license`) over `App\Entity\License` with a dual initial marking (`wait_medical_certificate_validation`, `wait_payment_validation`). Transitions (validate/reject medical certificate, validate payment, validate license) are guarded by the season admin roles above. Audit trail is enabled. Treat license state changes as workflow transitions, not direct property writes.

- **The initial marking is the `License::$marking` property default**, not something controllers apply. A workflow marking doesn't self-populate, and a license persisted with an empty marking is silently skipped by every state-filtered query in `LicenseRepository` (chain validation queue, exports, statistics) — so don't reset that default to `[]`, and keep it in sync with `initial_marking` in `config/packages/workflow.yaml` — `tests/Workflow/LicenseWorkflowTest.php` is what makes the two disagree loudly. Licenses are always stored with an explicit marking, never `[]`.
- `License::$marking` is a PostgreSQL **`json` column, which has no `=` operator** — never compare it directly in SQL. Query license state through the custom `JSON_GET_FIELD_AS_TEXT` DQL function (custom AST functions in `src/Doctrine/ORM/Query/AST/`, registered under `dql.string_functions` in `config/packages/doctrine.yaml`); `LicenseRepository` filters all state that way. For a whole-value comparison in raw SQL (e.g. a migration), cast with `marking::jsonb`.

### Doctrine event listeners
`src/EventListener/` is split by listener kind:
- `DoctrineEntity/` — per-entity listeners registered with `#[AsEntityListener(event:, entity:)]`, named for what they do (`ShellAbbreviationUpdater`). Use that attribute for new ones, not the older `#[Autoconfigure(tags: [['doctrine.orm.entity_listener' => …]])]` block.
- flat files — the flush-level listeners registered with `#[AsDoctrineListener(event:)]` (`ShellMileageUpdater`, `AutomaticTrainingCreator`, `UploadedFileRemover`).

Doctrine listeners are **shared services, so per-flush state must not survive the flush.** Stashing data in instance properties and reading it in a later event leaks it between entities in the same flush and between flushes — that was a real bug here (shells ended up with negative mileage). `ShellMileageUpdater` and `AutomaticTrainingCreator` are `readonly` and do everything in one pass over `getScheduledEntityInsertions()` / `…Updates()` / `…Deletions()`:

- Read old values from `$uow->getEntityChangeSet($entity)` and probe with `\array_key_exists`, **never `??`** — a field whose old value is legitimately `null` would otherwise fall back to the *new* value and the correction would be skipped.
- **For deletions, read values from `$uow->getOriginalEntityData($entity)`, not the getters.** `remove()` drops the entity from `entityUpdates` and `computeChangeSets()` skips anything scheduled for deletion, so its in-memory values may never have been persisted — editing a distance and deleting the entry in the same flush used to leave the shell's mileage drifted.
- After mutating another entity, call `$uow->recomputeSingleEntityChangeSet($em->getClassMetadata(X::class), $x)`, or no `UPDATE` is issued: `onFlush` fires *after* change sets are computed. Hoist the `getClassMetadata()` call above the loop.
- An entity **created** inside `onFlush` needs `persist()` **and** `$uow->computeChangeSet(...)`.
- On deletions, skip when the other side is going too (`$uow->isScheduledForDelete($shell)`).
- Don't guard preconditions with `\assert()`: production runs with `zend.assertions=-1`, so it is compiled out and the null it was meant to catch reaches the next line. `continue` instead.

`preUpdate` is not usable for these: it can only modify the entity it is passed. Prefer one `onFlush` over a `preUpdate`/`postUpdate` pair.

### Uploads
`FileUploader::upload()` writes to Flysystem and returns an **unpersisted** `UploadedFile`; the row is saved by cascade from its owner (`MedicalCertificate::$uploadedFile` — the only association to it — `OneToOne`, `cascade: ['persist', 'remove']`, `orphanRemoval: true`).

Deleting the **file** is not the controller's job — `App\EventListener\UploadedFileRemover` does it, and it is the one listener that has to hold state across two events:

- `onFlush` collects the `UploadedFile`s from `getScheduledEntityDeletions()`. That covers both routes into deletion, because `commit()` turns `orphanRemovals` into `remove()` calls *before* dispatching `onFlush` — the `remove()` cascade, and certificate replacement. It also calls `$em->initializeObject()` on each one: the row is still there at this point, and reading `filename` later would lazy-load a deleted entity (`EntityNotFoundException`).
- `postFlush` does the actual `FileUploader::remove()`. It is the **first event after the commit** — `preRemove` is not a flush event at all (it fires inside `$em->remove()`, before the `DELETE` is even issued, which is how a failed flush used to leave a live row pointing at nothing). Failures are logged, not thrown: the rows are already committed.
- The queue is **assigned, not appended** in `onFlush`, and drained in `postFlush`. A flush that throws in between leaves a stale queue that the next `onFlush` must discard, or it would delete files whose rows are still there.

Don't call `FileUploader::remove()` from application code. Trade-off to know: under DAMA the commit is a savepoint release, so the test's outer rollback still leaves the file deleted.

### Derived usernames
`User::$username` is `unique: true` and **derived, never entered**: the `#[ORM\PrePersist]`/`#[ORM\PreUpdate]` `defineUsername()` sets it from `User::buildUsername()` — an accent-folded slug of first + last name (`Léa Martin` → `lea.martin`). Renaming a member therefore changes their login identifier; that is intended.

The duplicate-member guard is `#[UniqueEntity(fields: ['firstName', 'lastName'], repositoryMethod: 'findForUniqueness')]`, and `UserRepository::findForUniqueness()` must compare **`user.username` built through the same `buildUsername()`**. Comparing the raw names instead (e.g. `LOWER(user.firstName)`) makes the validator disagree with the unique index — the slugger folds accents and spaces, `LOWER()` does not — so `Lea Martin` passes validation when `Léa Martin` exists and then hits the index. That is a 500 on the `PUBLIC_ACCESS` `/register/{slug}` form. Keep the two in sync.

Login is the one place that must **not** be kept in sync with `buildUsername()`. The username is a credential, so `UserRepository::loadUserByIdentifier()` deliberately does not try to reconstruct it from whatever was typed into `_username`: it only forgives typing (`->ascii()->trim()->lower()->replace(' ', '-')`) and looks the result up as-is. Members are expected to give the username they were issued.

Do not "fix" this by routing the identifier back through `buildUsername()` (splitting on the dot, re-slugging each half, …). Every such rule widens the set of inputs that resolve to one account, which is attack surface on an unauthenticated endpoint, and it buys nothing the member cannot get by typing their actual username. The known and accepted consequence: names the slugger rewrites are not reachable from their natural spelling — `D'Angelo` is stored as `d-angelo`, so `d'angelo.martin` will not log in. That is a deliberate trade, not a bug.

### Concept2 ergometer integration
Trainings can be imported from Concept2 (`log.concept2.com`): OAuth via `src/OAuth/Concept2Provider.php` + `Concept2OauthController`, API calls in `src/Service/Concept2ApiConsumer.php`, and the import runs **asynchronously** through Symfony Messenger — `Concept2ImportMessage` → `Concept2ImportMessageHandler`. Messenger transport is Doctrine (`MESSENGER_TRANSPORT_DSN=doctrine://default`).

`Training::$feeling` is a 0..1 scale entered by the member, so a `Training` nobody filled in gets the neutral middle, **`0.5`**. That value is currently a literal duplicated in the two automatic sources — `Concept2ApiConsumer` and the logbook's `AutomaticTrainingCreator` (which was on `5`, outside the 0..1 `Assert\Range`, until recently) — so keep them in step, and hoist it to a `Training` constant if a third source appears. `templates/admin/training/index.html.twig` averages the field into a per-member gauge, which any other default skews.

### Other services worth knowing
`src/Service/`: `FileUploader` (uploads via oneup/flysystem-bundle), `PdfGenerator` (uses Puppeteer via `bin/html-print.mjs`), `SeasonCsvGenerator`, `TrainingCalculator`/`TrainingHelper`, `ShellAbbreviationGenerator`. Charts in `src/Chart/` (symfony/ux-chartjs). Twig extension in `src/Twig/Extension/`.

## Testing

Functional tests extend `App\Tests\AppWebTestCase` (`tests/`), which mixes in Foundry's `Factories` + `ResetDatabase`. Key patterns:
- Build entities with **Zenstruck Foundry factories** (`src/Factory/*Factory.php`), not manual construction. Log a user with `$this->createAndLogin($client, 'ROLE_...')`.
- **DAMA DoctrineTestBundle** wraps each test in a transaction (rolled back) — no manual DB cleanup.
- PHPUnit is strict: `failOnDeprecation`/`failOnNotice`/`failOnWarning` are on. Symfony clock-mock and dns-mock are enabled for the `App` namespace.
- Same `*Factory` classes back both tests and `src/DataFixtures/`.
- **DAMA rolls back the database, not the filesystem.** `uploads_private_dir` is `var/uploads` in every environment, so any test creating a licence (`MedicalCertificateFactory` uploads a real PDF) leaves a file behind. Resolve paths with `FileUploader::getAbsolutePath()` rather than rebuilding `<dir>/<filename>` in the test.
- Don't build time-sensitive fixtures from **relative faker dates** when the code under test uses a pinned window — `dateTimeThisMonth()` is a rolling 30 days, not a calendar month, so a fixture window and a Monday-pinned query window only disagree on some weekdays. That made `TrainingControllerTest::testIndexTrainings` flaky. Pass explicit dates (`new \DateTime('monday this week')`).
- Prove a bug before fixing it, and mutation-check the fix: revert the production change and confirm the new test fails.

## Config notes

- Env: `.env` (committed defaults) + `.env.local` / `.env.test`. Secrets via `config/secrets/`.
- Local mail is caught by **Mailpit** (`compose.override.yaml`) instead of being sent.
- Deployment is **Ansible** — `deploy.yaml` / `rollback.yaml` (playbooks) + `hosts.yaml` (inventory: prod host `rhea.avirontours.fr`, `git_version: develop`). It rsyncs a release, runs migrations, and reloads PHP-FPM + Messenger workers. Release notes/version in `changelog.json` + `APP_VERSION`.
