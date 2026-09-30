# AGENTS.md

**Aviron Tours Métropole** management app: a Symfony / PHP web application for a
rowing club. Members & licenses, seasons, boats ("shells") & their damage
tracking, an outings logbook ("cahier des sorties"), trainings (with Concept2
ergometer sync), sport profiles, and physiological measures. User-facing strings
are in **French**. Exact versions are in `composer.json`; `symfony.lock` records
which recipes ran.

## Stack

Settled, don't ask about it:

- Doctrine ORM over PostgreSQL.
- Server-rendered Twig, Hotwired Stimulus/Turbo via Webpack Encore, Bootstrap 5 +
  SCSS.
- SecurityBundle: form login with email 2FA (see Security).
- Messenger over the Doctrine transport (`doctrine://default`).
- The Symfony local server serves the app; PostgreSQL and Mailpit run in Docker.

For anything else the task doesn't specify, ask rather than guess. If you can't
ask, state your assumption and pick the smallest option.

## Conventions

Idiomatic Symfony (https://symfony.com/doc/current/best_practices.html):

- **Flex, not hand-wiring**: add capabilities with `composer require <package>`
  and let the recipe register the bundle and write its config. Never hand-edit
  `config/bundles.php`. A good-fit component not yet installed is one command
  away; don't work around its absence.
- **Attributes for all framework metadata**: `#[Route]`, `#[IsGranted]`,
  `#[MapEntity]`, `#[MapQueryParameter]`, `#[Assert\...]`, `#[AsCommand]`,
  `#[AsEventListener]`, `#[AsMessageHandler]`, `#[AsAlias]`, `#[AsTaggedItem]`,
  `#[Autoconfigure]`. No YAML/XML routing.
- **Autowiring**: type-hint constructors; otherwise `#[Autowire]` (params, env,
  expressions) or `#[Target]`. A YAML service definition is the last resort.
- Controllers extend `AbstractController`, stay thin, delegate to services.
- Use the framework before hand-writing anything: Form, Validator, Serializer,
  Messenger, voters, Twig `path()`/`url()`, and Symfony components for locks,
  caches, HTTP clients, mailers, schedulers.
- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]`, never
  `json_decode()` or `SerializerInterface` by hand.
- Constructor property promotion; `readonly` for DTOs and value objects. Not on a
  service that might become `lazy: true` (a lazy proxy can't extend a `readonly`
  class).
- Mutual exclusion = `symfony/lock` (`LockFactory`), never a hand-built flag or
  lock file.

## Everyday workflow

PHP runs through the Symfony CLI (`symfony php`, `symfony console`), which
injects env vars from the Docker services. `make help` lists all targets.

- `symfony serve -d` runs the app.
- `make tests` is the pre-PR gate: `lint` (composer validate, yaml/twig/container
  lint, rector + php-cs-fixer dry-run, phpstan, eslint) +
  `doctrine:schema:validate` + phpunit (`--do-not-fail-on-deprecation`).
- `npm run build` must run before the test suite (controllers reference the
  Encore manifest). `npm run lint` = ESLint (`assets`, `bin`) + stylelint (SCSS).
- `make db-fixtures` resets the DB, loads fixtures, wipes `public/uploads` +
  `var/uploads`.
- On failure, read `var/log/dev.log` and `/_profiler` before changing code.
- `symfony console make:*` with every argument up front and `--no-interaction`:
  makers prompt otherwise and hang a non-interactive shell. If one still prompts,
  hand-write the code.
- Schema changes: `make:migration` then `doctrine:migrations:migrate`, never
  `doctrine:schema:update`.
- Config: `.env` (committed) holds defaults only, overridden by `.env.local` /
  `.env.test`. Real secrets go in the vault (`config/secrets/`,
  `secrets:set`), read via `%env(...)%`. Mailpit catches local mail.
- Deployment is Ansible (`deploy.yaml`, `rollback.yaml`, inventory `hosts.yaml`:
  `rhea.avirontours.fr`, `git_version: develop`): timestamped rsync release (3
  kept), migrations, PHP-FPM + Messenger workers reload.

## Architecture

### Security: two firewalls, one of them per host
`config/packages/security.yaml`:

- `main`: `App\Entity\User`, form login (`App\Security\LoginFormAuthenticator`),
  email 2FA (scheb/2fa), remember-me, `switch_user`.
- `logbook`: bound to `%logbook_host%` (from `DEFAULT_LOGBOOK_URI`), HTTP Basic
  against an in-memory `logbook` user. It is the outings logbook, opened on a
  dedicated computer in the boathouse.

Load-bearing details, each pinned by a test or a past incident:

- The `logbook` firewall has **no `pattern:`** on purpose. With one, the other
  URLs on that host fall through to `main` and a member's session would reach
  them from the boathouse machine. The catch-all
  `{ path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }` is satisfied by Basic.
- `access_control` is first-match-wins and its `PUBLIC_ACCESS` rules carry no
  `host:`, so `{ host: '%logbook_host%', path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }`
  **must stay the first rule**. Otherwise `/login`, `/register`,
  `/reset-password`, `/legal-notice`, `/privacy-policy`, `/payment-attestation`
  are served on the logbook host with no Basic challenge.
  `testPublicPagesAreBasicAuthenticatedOnTheLogbookHost` pins it (302 instead
  of 401).
- `App\EventSubscriber\LogbookHostFirewallSubscriber` redirects anything whose
  `_route` isn't `logbook_entry_*` to `logbook_entry_index`. The firewall decides
  *who* gets in, the subscriber *what* is reachable.
  - Its `kernel.request` **priority 0 must stay**: `_route` is set by
    `RouterListener` (32), the firewall answers at 8. Earlier, `_route` is null
    and every request, `logbook_entry_index` included, redirects in a loop.
  - Its dev-path whitelist `^/(_profiler|_wdt|assets|build)/` duplicates the
    `dev` firewall's `pattern:`. **Edit both together.**

**Roles** (grant sub-areas, not a blanket ROLE_ADMIN): `ROLE_SUPER_ADMIN` →
`ROLE_ADMIN` → { `ROLE_LOGBOOK_ADMIN`, `ROLE_MATERIAL_ADMIN`, `ROLE_SPORT_ADMIN`,
`ROLE_USER_ADMIN`, `ROLE_SEASON_ADMIN` }; `ROLE_SEASON_ADMIN` →
{ `ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN`, `ROLE_SEASON_PAYMENTS_ADMIN` }. Each
controller in `src/Controller/Admin/` guards on its specific role.

Member-facing pages are commonly gated by `is_granted("VALID_LICENSE")`
(sometimes OR-ed with `ROLE_ADMIN`), backed by `App\Security\Voter\ValidLicenseVoter`
→ `LicenseRepository::hasValidLicenseForActiveSeason()` (one COUNT, memoised per
request).

### License workflow
`config/packages/workflow.yaml` defines the `license` workflow over
`App\Entity\License`: dual initial marking (`wait_medical_certificate_validation`,
`wait_payment_validation`), transitions guarded by the season admin roles, audit
trail on. Change license state through transitions, never direct writes.

- **The initial marking is the `License::$marking` property default**, kept in
  sync with `initial_marking` (`tests/Workflow/LicenseWorkflowTest.php` fails if
  they drift). Never reset it to `[]`: an empty marking is silently skipped by
  every state-filtered query in `LicenseRepository`.
- `marking` is a PostgreSQL **`json` column, which has no `=`**. Query state with
  the custom `JSON_GET_FIELD_AS_TEXT` DQL function (`src/Doctrine/ORM/Query/AST/`,
  registered in `config/packages/doctrine.yaml`); in raw SQL cast with
  `marking::jsonb`.

### Doctrine listeners
- `src/EventListener/DoctrineEntity/`: per-entity listeners with
  `#[AsEntityListener(event:, entity:)]`, named for what they do
  (`ShellAbbreviationUpdater`). Don't use the old
  `#[Autoconfigure(tags: [...entity_listener...])]` form.
- `src/EventListener/*.php`: flush-level listeners with
  `#[AsDoctrineListener(event:)]` (`ShellMileageUpdater`,
  `AutomaticTrainingCreator`, `UploadedFileRemover`).

Listeners are shared services: **per-flush state must not survive the flush**
(instance-property stashing once gave shells negative mileage). Do everything in
one `onFlush` pass over `getScheduledEntityInsertions()` / `…Updates()` /
`…Deletions()`, in a `readonly` class:

- Old values: `$uow->getEntityChangeSet($entity)`, probed with
  `\array_key_exists`, **never `??`** (a legitimately `null` old value would fall
  back to the new one).
- Deletions: read `$uow->getOriginalEntityData($entity)`, **not the getters**
  (in-memory edits on a removed entity were never persisted).
- After mutating another entity:
  `$uow->recomputeSingleEntityChangeSet($em->getClassMetadata(X::class), $x)`,
  metadata hoisted above the loop, or no `UPDATE` is issued.
- An entity created in `onFlush` needs `persist()` **and**
  `$uow->computeChangeSet(...)`.
- Skip when the other side is also scheduled for deletion
  (`$uow->isScheduledForDelete($shell)`).
- No `\assert()` for preconditions (prod has `zend.assertions=-1`); `continue`.
- Not `preUpdate` (it can only modify its own entity), nor a
  `preUpdate`/`postUpdate` pair.

### Uploads
`FileUploader::upload()` writes to Flysystem and returns an **unpersisted**
`UploadedFile`, saved by cascade from `MedicalCertificate::$uploadedFile` (its only
association: `OneToOne`, `cascade: ['persist', 'remove']`, `orphanRemoval: true`).

Deleting the file belongs to `App\EventListener\UploadedFileRemover` alone; never
call `FileUploader::remove()` from application code. It is the one listener
holding state across two events:

- `onFlush` **assigns** (not appends) its queue from
  `getScheduledEntityDeletions()`, which covers both cascade removal and
  certificate replacement (orphan removals are already `remove()` calls by then).
  It calls `$em->initializeObject()` on each, or reading `filename` later hits
  `EntityNotFoundException`. Assigning discards a stale queue left by a flush
  that threw.
- `postFlush`, the first event after commit, removes the files and logs failures
  instead of throwing. Not `preRemove`: it fires before the `DELETE`, so a failed
  flush left a live row pointing at nothing.

Under DAMA the commit is a savepoint release, so tests still delete the file.

### Derived usernames
`User::$username` is unique and **derived, never entered**:
`defineUsername()` (`PrePersist`/`PreUpdate`) sets it from `User::buildUsername()`,
an accent-folded slug of first + last name (`Léa Martin` → `lea.martin`).
Renaming a member changes their login, intentionally.

- The `#[UniqueEntity(..., repositoryMethod: 'findForUniqueness')]` guard must
  compare `user.username` built through the same `buildUsername()`. Comparing raw
  names (`LOWER()`) lets `Lea Martin` pass validation when `Léa Martin` exists,
  then hit the index: a 500 on the public `/register/{slug}` form.
- Login must **not** go through `buildUsername()`.
  `UserRepository::loadUserByIdentifier()` only forgives typing
  (`->ascii()->trim()->lower()->replace(' ', '-')`) and looks the result up
  as-is. Reconstructing the username from a typed name widens the inputs that
  resolve to one account on an unauthenticated endpoint. Accepted consequence:
  `D'Angelo` is stored as `d-angelo`, so `d'angelo.martin` won't log in. Not a
  bug.

### Concept2 integration
OAuth via `src/OAuth/Concept2Provider.php` + `Concept2OauthController`, API calls
in `App\Service\Concept2\Concept2ApiConsumer` (failures:
`App\Service\Concept2\Exception\*`). The sync (`POST training_import_concept_logbook`)
refreshes the token synchronously, then dispatches one
`Concept2ResultImportMessage` per new result, handled asynchronously by
`Concept2ResultImportMessageHandler`. Workers never refresh the token (parallel
refreshes would race the refresh-token rotation).

**Automatic sources record what they measured and answer nothing for the
member**: Concept2 and the logbook's `AutomaticTrainingCreator` leave `feeling`
null so `training/_show.html.twig` offers the rating form. Don't reintroduce a
default. 1 112 historical rows still carry the old automatic middle value, so
`templates/admin/training/index.html.twig` averages only `feeling is not null`
sessions; a member with none has no gauge, not a zero.

### Sports and colours
Sports have no colour of their own: `SportType::specificity()` maps each case to
`App\Enum\SportSpecificity` (Spécifique = Aviron, Semi-spécifique = Ergomètre,
Non-spécifique = the rest), which holds the label and the colour used by the
chips, the sport picker, the weekly bar and `TrainingVolumeChart`. A new sport
needs one `match` arm there, nothing else.

### Frontend
- **Layouts**: `templates/layouts/document.html.twig` is the HTML root; pages
  extend one of its frames (`app`, `auth`, `focus`, `logbook`), never branch
  inside one.
- **Twig components**: `templates/components/` holds anonymous components
  (symfony/ux-twig-component). Use them over raw Bootstrap markup
  (`<twig:Button variant="danger" href="…">`, `<twig:Card:Body>`). Idiom:
  `{% props %}` declares the API, `html_cva()` maps variants, `attributes.defaults()`
  lets call sites add classes. The header comment is the API contract; read it
  before adding a prop.
- **Icons**: symfony/ux-icons over `assets/icons/`
  (`<twig:ux:icon name="mdi:menu" />`). Names stay **literal in the markup**
  (`ux:icons:lock` collects them statically), so components take icons in their
  body, never as a prop.
- **SCSS**: the cascade order documented in `assets/styles/app.scss` is
  load-bearing: `_custom.scss` → Bootstrap → `_tokens.scss` → `_utilities.scss` →
  `layouts/` → `components/` → `pages/` → `_turbo.scss`. One file per
  frame/component/page, naming its owning template on line 1; a file whose
  template is gone is dead code; a `pages/` rule a second page needs moves to
  `components/`. Anything after Bootstrap's utilities outranks them at equal
  specificity and can silently defeat a utility class.
- **Stimulus** (`assets/controllers/`): new controllers in TypeScript; legacy ones
  stay `.js`.

Other services in `src/Service/`: `FileUploader` (oneup/flysystem),
`PdfGenerator` (Puppeteer via `bin/html-print.mjs`), `SeasonCsvGenerator`,
`TrainingHelper`, `ShellAbbreviationGenerator`. Charts in `src/Chart/`
(symfony/ux-chartjs).

## Testing

A feature isn't done without a test that exercises it the way a caller would (an
HTTP request for a controller, a service call for a service).

- Single test: `symfony php vendor/bin/phpunit --filter testName` or by path.
- Functional tests extend `App\Tests\AppWebTestCase` (Foundry `Factories` +
  `ResetDatabase`); service tests extend `KernelTestCase`. Log in with
  `$this->createAndLogin($client, 'ROLE_...')`.
- Build entities with Foundry factories (`src/Factory/`), shared with
  `src/DataFixtures/`.
- DAMA wraps each test in a rolled-back transaction: no DB cleanup. It does
  **not** roll back the filesystem: `MedicalCertificateFactory` uploads a real PDF
  to `var/uploads`. Resolve paths with `FileUploader::getAbsolutePath()`.
- PHPUnit fails on deprecations, notices and warnings. Clock-mock and dns-mock
  are enabled for `App`.
- No relative faker dates against a pinned window (`dateTimeThisMonth()` is a
  rolling 30 days, which made `testIndexTrainings` flaky on some weekdays). Pass
  explicit dates (`new \DateTime('monday this week')`).
- Prove a bug before fixing it, then mutation-check: revert the fix and confirm
  the test fails.

## Code style

`@Symfony` + `@Symfony:risky` php-cs-fixer, strict comparisons, `mb_str_functions`,
ordered imports/class elements; PHPStan level 5. CI fails on:

- A PHP file without `declare(strict_types=1);` and the Apache-2.0 header
  (`Copyright 2020 Mathieu Piot`, copy it from any file in `src/`).
- Rector / php-cs-fixer drift. Run the fixers, don't hand-tweak:
  `symfony php vendor/bin/php-cs-fixer fix`, `symfony php vendor/bin/rector process`.
- `ordered_class_elements`: **static methods go last**, after all instance
  methods.

Not caught by the tooling:

- Remove unused `use function` imports by hand (`no_unused_imports` skips them).
- Prefer string interpolation (`"{$dir}/{$filename}"`) over `.` concatenation;
  extract a local for a long method chain.

## Discover, don't guess

Your training data may predate the installed versions. Check the project:
`symfony console about`, `debug:router`, `debug:container`,
`debug:autowiring <name>`, `debug:config <bundle>`,
`config:dump-reference <bundle>`, `lint:container`, `lint:twig templates/`,
`lint:yaml config/`, the source under `vendor/`, and
https://symfony.com/doc/current/ for the version in `composer.json`.
