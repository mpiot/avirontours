# AGENTS.md

**Aviron Tours Métropole** management app: a Symfony 8.1 / PHP 8.5 web application
for a rowing club. Members & licenses, seasons, boats ("shells") & their damage
tracking, an outings logbook ("cahier des sorties"), trainings (with Concept2
ergometer sync), sport profiles, and physiological measures. User-facing strings
are in **French**.

`composer.json` has the exact Symfony/PHP versions, and `symfony.lock` records
which recipes ran.

## Stack

The stack is settled, don't ask about it:

- Persistence: Doctrine ORM over PostgreSQL.
- Interface: server-rendered Twig, with Hotwired Stimulus/Turbo via Webpack
  Encore, Bootstrap 5 + SCSS. New Stimulus controllers are TypeScript.
- Auth: SecurityBundle, form login with email 2FA (see Security below).
- Async: Messenger over the Doctrine transport
  (`MESSENGER_TRANSPORT_DSN=doctrine://default`).

The frontend is served by the Symfony local server; PostgreSQL and Mailpit run
in Docker.

For anything else the task doesn't specify, ask rather than guess. If you can't
ask (no interactive channel), state the assumption you're making and pick the
smallest option rather than scaffolding something nobody asked for.

## Adding features: Flex, not hand-wiring

Install new capabilities with `composer require <package>` (e.g. `symfony/lock`)
and let the Flex recipe register the bundle and generate its config. Don't
hand-edit `config/bundles.php` or hand-write a bundle's base config; that's what
the recipe is for. Don't skip a good-fit component just because it isn't
installed yet; installing it is one command.

## Conventions

Follow https://symfony.com/doc/current/best_practices.html to write idiomatic
Symfony:

- Use PHP attributes for framework metadata, and not only on controllers:
  `#[Route]`, `#[IsGranted]`, `#[MapEntity]`, `#[MapQueryParameter]`,
  `#[MapRequestPayload]` on actions, `#[Assert\...]` on properties,
  `#[AsCommand]`, `#[AsEventListener]`, `#[AsMessageHandler]`, and
  `#[AsAlias]` / `#[AsTaggedItem]` / `#[Autoconfigure]` on services. No YAML or
  XML routing.
- Rely on autowiring and autoconfiguration. Type-hint constructor arguments and
  let the container resolve them. Where a type-hint can't express it, stay in the
  class with `#[Autowire]` (parameters, env vars, expressions) or `#[Target]` (one
  of several implementations of an interface). A YAML service definition is the
  last resort, not the first.
- Controllers extend `AbstractController`, stay thin, and delegate to services.
- Use the framework for what it already does: Form for server-rendered forms,
  Validator for validation, Serializer for JSON, Messenger for async work,
  Security (voters, authenticators) for access control, Twig `path()`/`url()`
  instead of hardcoded URLs.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
  Don't mark a service `readonly` if it might become `lazy: true`: a lazy proxy
  can't extend a `readonly` class.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion. A hand-built flag or
  lock file looks fine in review and is usually wrong under concurrency.

## Everyday workflow

PHP runs through the Symfony CLI (`symfony php`, `symfony console`), which
injects env vars from the Docker services. `make help` lists everything; the
targets that matter when changing code:

- Run the app with `symfony serve -d`.
- `make tests`: the full pre-PR gate, `lint` + `doctrine:schema:validate` +
  `phpunit`. `lint` covers composer validate, yaml/twig/container lint, rector
  dry-run, php-cs-fixer dry-run, phpstan, eslint. (The Makefile runs phpunit with
  `--do-not-fail-on-deprecation`.)
- Frontend: `npm run build` (Encore) must run before the test suite, because
  controllers reference the Encore manifest. `npm run lint` is ESLint over
  `assets` + `bin` plus stylelint over `assets/styles/**/*.scss`.
- `make db-fixtures`: reset the database and load the Doctrine fixtures (also
  wipes `public/uploads` + `var/uploads`).
- When something fails, read `var/log/dev.log` and the web profiler
  (`/_profiler`) before changing code.
- Prefer `symfony console make:*` (maker-bundle is installed) with every argument
  passed up front and `--no-interaction` where supported: makers prompt on a
  terminal by default, which hangs a non-interactive shell. If a maker still
  needs interactive input, hand-write the code instead.
- Schema changes go through migrations (`symfony console make:migration`, then
  `doctrine:migrations:migrate`), never `doctrine:schema:update`.

### Config

- Env: `.env` is committed and holds defaults only, overridden by `.env.local`
  (git-ignored) / `.env.test`. Real secrets belong in the secrets vault
  (`config/secrets/`, `symfony console secrets:set`), read via `%env(...)%`.
- Local mail is caught by **Mailpit** (`compose.override.yaml`) instead of being
  sent.
- Deployment is **Ansible**: `deploy.yaml` / `rollback.yaml` (playbooks) +
  `hosts.yaml` (inventory: prod host `rhea.avirontours.fr`,
  `git_version: develop`). It rsyncs a timestamped release (3 kept), runs
  migrations, and reloads PHP-FPM + Messenger workers.

## Architecture

Standard Symfony layout: `App\` → `src/`, `App\Tests\` → `tests/`. Controllers
use PHP 8 attributes throughout.

### Security & multi-firewall / subdomain design
`config/packages/security.yaml` defines **two firewalls**:
- `main`: app users (`App\Entity\User`), form login via
  `App\Security\LoginFormAuthenticator`, **email-based 2FA** (scheb/2fa),
  remember-me, and `switch_user` impersonation.
- `logbook`: bound to a **separate host** (`%logbook_host%`, from
  `DEFAULT_LOGBOOK_URI`) with **no `pattern:`**, so it covers every URL on that
  host, using HTTP Basic against an in-memory `logbook` user. This is the outings
  logbook on its own subdomain, opened on a dedicated computer in the boathouse
  that members use to record their outings.

  Scoping the firewall by host alone is deliberate: with a `pattern:` the other
  URLs on that host would fall through to the `main` firewall and be served under
  *its* session, so a member logged in on the main site would reach them on the
  logbook machine. The whole host now answers under the `logbook` firewall
  instead, and the catch-all `- { path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }`
  rule is satisfied by Basic (`AuthenticatedVoter` grants
  `IS_AUTHENTICATED_REMEMBERED` to any full-fledged token).

  **`access_control` is first-match-wins, and its `PUBLIC_ACCESS` rules carry no
  `host:`**, so
  `- { host: '%logbook_host%', path: ^/, roles: IS_AUTHENTICATED_REMEMBERED }`
  **must stay the first rule**. Without it `^/login$`, `^/register`,
  `^/reset-password`, `^/mentions-legales$` and `^/payment-attestation` match on
  the logbook host too and are served with no Basic challenge, leaving
  `App\EventSubscriber\LogbookHostFirewallSubscriber` as the sole control over the
  member-facing surface there. `testPublicPagesAreBasicAuthenticatedOnTheLogbookHost`
  pins it: drop the rule and those paths answer 302 instead of 401.

  The subscriber is still what shapes the host into a logbook: it redirects
  anything whose `_route` is not `logbook_entry_*` back to `logbook_entry_index`.
  Belt and braces: the firewall decides *who* gets in, the subscriber decides
  *what* is reachable once in.

  Its `kernel.request` **priority 0 is pinned explicitly**, and must stay there:
  `_route` is only set by `RouterListener` (priority 32) and the firewall answers
  at priority 8. Running earlier means `_route` is null,
  `u(null)->startsWith('logbook_entry_')` is `false`, and *every* request on the
  host redirects, `logbook_entry_index` included, i.e. an infinite loop that takes
  the logbook offline. The dev paths it lets through
  (`^/(_profiler|_wdt|assets|build)/`) are a **hand-copied duplicate of the `dev`
  firewall's `pattern:`** in `security.yaml`, and the two must be edited together.
  Add a path to the dev firewall alone and the subscriber 302s it to
  `logbook_entry_index` on the logbook host, killing the toolbar or the assets;
  drop one from `security.yaml` alone and it stays whitelisted past the redirect.

**Role hierarchy** (grant admin sub-areas, not a single ROLE_ADMIN):
`ROLE_SUPER_ADMIN` → `ROLE_ADMIN` → { `ROLE_LOGBOOK_ADMIN`, `ROLE_MATERIAL_ADMIN`,
`ROLE_SPORT_ADMIN`, `ROLE_USER_ADMIN`, `ROLE_SEASON_ADMIN` }; `ROLE_SEASON_ADMIN` →
{ `ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN`, `ROLE_SEASON_PAYMENTS_ADMIN` }. Admin
controllers live in `src/Controller/Admin/` and each guards on its specific role.

Non-admin feature access is commonly gated by `is_granted("VALID_LICENSE")`
(sometimes OR-ed with `ROLE_ADMIN` in an `Expression`). The
`App\Security\Voter\ValidLicenseVoter` backs that attribute, delegating to
`LicenseRepository::hasValidLicenseForActiveSeason()` (a single COUNT query,
memoised per request). Check there when reasoning about member-facing pages.

### License validation workflow
`config/packages/workflow.yaml` defines a **Symfony Workflow** (`license`) over
`App\Entity\License` with a dual initial marking
(`wait_medical_certificate_validation`, `wait_payment_validation`). Transitions
(validate/reject medical certificate, validate payment, validate license) are
guarded by the season admin roles above. Audit trail is enabled. Treat license
state changes as workflow transitions, not direct property writes.

- **The initial marking is the `License::$marking` property default**, not
  something controllers apply. A workflow marking doesn't self-populate, and a
  license persisted with an empty marking is silently skipped by every
  state-filtered query in `LicenseRepository` (chain validation queue, exports,
  statistics). So don't reset that default to `[]`, and keep it in sync with
  `initial_marking` in `config/packages/workflow.yaml`;
  `tests/Workflow/LicenseWorkflowTest.php` is what makes the two disagree loudly.
  Licenses are always stored with an explicit marking, never `[]`.
- `License::$marking` is a PostgreSQL **`json` column, which has no `=`
  operator**: never compare it directly in SQL. Query license state through the
  custom `JSON_GET_FIELD_AS_TEXT` DQL function (custom AST functions in
  `src/Doctrine/ORM/Query/AST/`, registered under `dql.string_functions` in
  `config/packages/doctrine.yaml`); `LicenseRepository` filters all state that
  way. For a whole-value comparison in raw SQL (e.g. a migration), cast with
  `marking::jsonb`.

### Doctrine event listeners
`src/EventListener/` is split by listener kind:
- `DoctrineEntity/`: per-entity listeners registered with
  `#[AsEntityListener(event:, entity:)]`, named for what they do
  (`ShellAbbreviationUpdater`). Use that attribute for new ones, not the older
  `#[Autoconfigure(tags: [['doctrine.orm.entity_listener' => …]])]` block.
- flat files: the flush-level listeners registered with
  `#[AsDoctrineListener(event:)]` (`ShellMileageUpdater`,
  `AutomaticTrainingCreator`, `UploadedFileRemover`).

Doctrine listeners are **shared services, so per-flush state must not survive
the flush.** Stashing data in instance properties and reading it in a later event
leaks it between entities in the same flush and between flushes; that was a real
bug here (shells ended up with negative mileage). `ShellMileageUpdater` and
`AutomaticTrainingCreator` are `readonly` and do everything in one pass over
`getScheduledEntityInsertions()` / `…Updates()` / `…Deletions()`:

- Read old values from `$uow->getEntityChangeSet($entity)` and probe with
  `\array_key_exists`, **never `??`**: a field whose old value is legitimately
  `null` would otherwise fall back to the *new* value and the correction would be
  skipped.
- **For deletions, read values from `$uow->getOriginalEntityData($entity)`, not
  the getters.** `remove()` drops the entity from `entityUpdates` and
  `computeChangeSets()` skips anything scheduled for deletion, so its in-memory
  values may never have been persisted. Editing a distance and deleting the entry
  in the same flush used to leave the shell's mileage drifted.
- After mutating another entity, call
  `$uow->recomputeSingleEntityChangeSet($em->getClassMetadata(X::class), $x)`, or
  no `UPDATE` is issued: `onFlush` fires *after* change sets are computed. Hoist
  the `getClassMetadata()` call above the loop.
- An entity **created** inside `onFlush` needs `persist()` **and**
  `$uow->computeChangeSet(...)`.
- On deletions, skip when the other side is going too
  (`$uow->isScheduledForDelete($shell)`).
- Don't guard preconditions with `\assert()`: production runs with
  `zend.assertions=-1`, so it is compiled out and the null it was meant to catch
  reaches the next line. `continue` instead.

`preUpdate` is not usable for these: it can only modify the entity it is passed.
Prefer one `onFlush` over a `preUpdate`/`postUpdate` pair.

### Uploads
`FileUploader::upload()` writes to Flysystem and returns an **unpersisted**
`UploadedFile`; the row is saved by cascade from its owner
(`MedicalCertificate::$uploadedFile`, the only association to it: `OneToOne`,
`cascade: ['persist', 'remove']`, `orphanRemoval: true`).

Deleting the **file** is not the controller's job.
`App\EventListener\UploadedFileRemover` does it, and it is the one listener that
has to hold state across two events:

- `onFlush` collects the `UploadedFile`s from `getScheduledEntityDeletions()`.
  That covers both routes into deletion, because `commit()` turns
  `orphanRemovals` into `remove()` calls *before* dispatching `onFlush`: the
  `remove()` cascade, and certificate replacement. It also calls
  `$em->initializeObject()` on each one: the row is still there at this point,
  and reading `filename` later would lazy-load a deleted entity
  (`EntityNotFoundException`).
- `postFlush` does the actual `FileUploader::remove()`. It is the **first event
  after the commit**. `preRemove` is not a flush event at all (it fires inside
  `$em->remove()`, before the `DELETE` is even issued, which is how a failed flush
  used to leave a live row pointing at nothing). Failures are logged, not thrown:
  the rows are already committed.
- The queue is **assigned, not appended** in `onFlush`, and drained in
  `postFlush`. A flush that throws in between leaves a stale queue that the next
  `onFlush` must discard, or it would delete files whose rows are still there.

Don't call `FileUploader::remove()` from application code. Trade-off to know:
under DAMA the commit is a savepoint release, so the test's outer rollback still
leaves the file deleted.

### Derived usernames
`User::$username` is `unique: true` and **derived, never entered**: the
`#[ORM\PrePersist]`/`#[ORM\PreUpdate]` `defineUsername()` sets it from
`User::buildUsername()`, an accent-folded slug of first + last name
(`Léa Martin` → `lea.martin`). Renaming a member therefore changes their login
identifier; that is intended.

The duplicate-member guard is
`#[UniqueEntity(fields: ['firstName', 'lastName'], repositoryMethod: 'findForUniqueness')]`,
and `UserRepository::findForUniqueness()` must compare **`user.username` built
through the same `buildUsername()`**. Comparing the raw names instead (e.g.
`LOWER(user.firstName)`) makes the validator disagree with the unique index (the
slugger folds accents and spaces, `LOWER()` does not), so `Lea Martin` passes
validation when `Léa Martin` exists and then hits the index. That is a 500 on the
`PUBLIC_ACCESS` `/register/{slug}` form. Keep the two in sync.

Login is the one place that must **not** be kept in sync with `buildUsername()`.
The username is a credential, so `UserRepository::loadUserByIdentifier()`
deliberately does not try to reconstruct it from whatever was typed into
`_username`: it only forgives typing
(`->ascii()->trim()->lower()->replace(' ', '-')`) and looks the result up as-is.
Members are expected to give the username they were issued.

Do not "fix" this by routing the identifier back through `buildUsername()`
(splitting on the dot, re-slugging each half, …). Every such rule widens the set
of inputs that resolve to one account, which is attack surface on an
unauthenticated endpoint, and it buys nothing the member cannot get by typing
their actual username. The known and accepted consequence: names the slugger
rewrites are not reachable from their natural spelling. `D'Angelo` is stored as
`d-angelo`, so `d'angelo.martin` will not log in. That is a deliberate trade, not
a bug.

### Concept2 ergometer integration
Trainings can be imported from Concept2 (`log.concept2.com`): OAuth via
`src/OAuth/Concept2Provider.php` + `Concept2OauthController`, API calls in
`App\Service\Concept2\Concept2ApiConsumer` (its failures are
`App\Service\Concept2\Exception\*`). The member's sync
(`POST training_import_concept_logbook`) is synchronous: it refreshes the stored
token, lists the new results and dispatches one `Concept2ResultImportMessage` per
result, imported **asynchronously** by `Concept2ResultImportMessageHandler`.
Workers only read the stored token and never refresh it: parallel refreshes
would race the refresh token rotation.

**An automatic source records what it measured and answers nothing for the
member.** `Concept2ApiConsumer` and the logbook's `AutomaticTrainingCreator` both
used to stamp `feeling` with the neutral middle; both columns are nullable now and
both leave them alone, which is what makes `training/_show.html.twig` offer the
rating form instead of showing a value nobody gave. Don't reintroduce a default
there.

The history was not rewritten: 1 112 rows still carry that automatic middle and
are indistinguishable from a deliberate "Moyen", so
`templates/admin/training/index.html.twig` averages **only the sessions that were
answered**. Filter on `feeling is not null` before reducing, and a member who
answered none has no gauge rather than a zero.

### Other services worth knowing
`src/Service/`: `FileUploader` (uploads via oneup/flysystem-bundle),
`PdfGenerator` (uses Puppeteer via `bin/html-print.mjs`), `SeasonCsvGenerator`,
`TrainingHelper`, `ShellAbbreviationGenerator`. Charts in `src/Chart/`
(symfony/ux-chartjs). Twig extension in `src/Twig/Extension/`.

Sports carry no colour of their own: `SportType::specificity()` maps each case to
`App\Enum\SportSpecificity` (Spécifique = Aviron, Semi-spécifique = Ergomètre,
Non-spécifique = everything else), and that enum holds the label and the colour
the chips, the sport picker, the weekly bar and `TrainingVolumeChart` use. A new
sport (FIT import, Polar, Garmin…) needs a `match` arm there and nothing else;
the palette is three fixed, validated colours.

### Frontend: layouts, Twig components, SCSS
The UI is Bootstrap 5 under a small design system with three layers:

- **Layouts**: `templates/layouts/document.html.twig` is the HTML root; pages
  extend one of its frames: `app` (sidebar shell), `auth`, `focus`, `logbook`.
  Pick the frame by extending it, not with conditionals inside one template.
- **Twig components**: `templates/components/` holds ~35 **anonymous** components
  (symfony/ux-twig-component; no PHP component classes yet). Use them instead of
  raw Bootstrap markup: `<twig:Button variant="danger" href="…">`; compound
  children live in subdirectories (`<twig:Card:Body>`, `<twig:Dropdown:Item>`).
  House idiom: `{% props %}` declares the API, `html_cva()` maps variants to
  classes, `attributes.defaults()` lets call sites add classes. Each component's
  header comment is its API contract; read it before adding a prop.
- **Icons**: symfony/ux-icons over local SVGs in `assets/icons/`
  (`<twig:ux:icon name="mdi:menu" />`). Icon names must stay **literal in the
  markup** (`ux:icons:lock` collects them statically), so components take icons
  in their body, never as a prop.
- **SCSS**: `assets/styles/app.scss` documents its own cascade order and the
  order is load-bearing: `_custom.scss` (Bootstrap variables, before Bootstrap) →
  Bootstrap → `_tokens.scss` → `_utilities.scss` → `layouts/` → `components/` →
  `pages/` → `_turbo.scss`. One file per layout frame / component / page, each
  naming its owning template on its first line. A file whose template is gone is
  dead code, and a `pages/` rule a second page wants gets promoted to
  `components/`. Everything after Bootstrap's utilities outranks them at equal
  specificity, so a rule there can silently defeat a utility class used in a
  template.

Stimulus controllers (`assets/controllers/`) are **TypeScript for new code**
(`modal_controller.ts`, …); legacy ones remain `.js`.

## Testing

A feature isn't done until it has a test that exercises it the way a caller
would, an HTTP request for a controller or a service call for a service, not just
"it didn't throw."

- Run a **single test** with `symfony php vendor/bin/phpunit --filter TestMethodName`
  or by path, e.g.
  `symfony php vendor/bin/phpunit tests/Controller/TrainingControllerTest.php`.
  (CI uses `bin/phpunit`.)
- Functional tests extend `App\Tests\AppWebTestCase` (itself a `WebTestCase`),
  which mixes in Foundry's `Factories` + `ResetDatabase`; service-level tests
  extend `KernelTestCase`.
- Build entities with **Zenstruck Foundry factories** (`src/Factory/*Factory.php`),
  not manual construction. Log a user with
  `$this->createAndLogin($client, 'ROLE_...')`. The same `*Factory` classes back
  both tests and `src/DataFixtures/`.
- **DAMA DoctrineTestBundle** wraps each test in a transaction (rolled back), so
  no manual DB cleanup.
- PHPUnit is strict: `failOnDeprecation`/`failOnNotice`/`failOnWarning` are on.
  Symfony clock-mock and dns-mock are enabled for the `App` namespace.
- **DAMA rolls back the database, not the filesystem.** `uploads_private_dir` is
  `var/uploads` in every environment, so any test creating a licence
  (`MedicalCertificateFactory` uploads a real PDF) leaves a file behind. Resolve
  paths with `FileUploader::getAbsolutePath()` rather than rebuilding
  `<dir>/<filename>` in the test.
- Don't build time-sensitive fixtures from **relative faker dates** when the code
  under test uses a pinned window. `dateTimeThisMonth()` is a rolling 30 days, not
  a calendar month, so a fixture window and a Monday-pinned query window only
  disagree on some weekdays. That made `TrainingControllerTest::testIndexTrainings`
  flaky. Pass explicit dates (`new \DateTime('monday this week')`).
- Prove a bug before fixing it, and mutation-check the fix: revert the production
  change and confirm the new test fails.

## Code style

Symfony's coding standard: the `@Symfony` + `@Symfony:risky` php-cs-fixer
rulesets, plus strict comparisons (`strict_comparison`, `strict_param`),
`mb_str_functions`, ordered imports/class elements. PHPStan runs at **level 5**
with Doctrine/Symfony/strict/deprecation extensions. CI will fail on any of the
following:

- **Every PHP file** must start with `declare(strict_types=1);` and the
  Apache-2.0 file header comment (`Copyright 2020 Mathieu Piot`). Enforced by
  php-cs-fixer's `header_comment` + `declare_strict_types`. Copy the header from
  any existing file in `src/`.
- Rector and php-cs-fixer both gate the PR. When lint fails, run their fixers,
  don't hand-tweak style: `symfony php vendor/bin/php-cs-fixer fix` and
  `symfony php vendor/bin/rector process`.
- `ordered_class_elements` puts **static methods last**, after the
  public/protected/private instance methods: a new `public static` helper goes at
  the bottom of the class, not next to its instance caller.
- `no_unused_imports` does **not** cover `use function` imports; remove those by
  hand when the last call goes away.

Not enforced by the fixers, so `make tests` will not catch it: prefer **string
interpolation** (`"{$dir}/{$filename}"`) over `.` concatenation; extract a local
when the expression is a long method chain.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `symfony console about`: versions, environment, paths.
- `symfony console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `symfony console lint:container`, `lint:twig templates/` and
  `lint:yaml config/`: validate before running.
- Read the installed source and docblocks under `vendor/`.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs).
