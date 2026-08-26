# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`spiriitlabs/form-filter-bundle` — a Symfony bundle (formerly `LexikFormFilterBundle`) that turns a Symfony `Form` into Doctrine ORM query-builder conditions. The user composes filter form types (e.g. `TextFilterType`, `NumberFilterType`), then `FilterBuilderUpdater::addFilterConditions($form, $queryBuilder)` walks the form tree and attaches `WHERE` clauses to the query builder.

The root namespace is `Spiriit\Bundle\FormFilterBundle\` mapped to the repository root via PSR-4 (no `src/` directory). Bundle class is `SpiriitFormFilterBundle.php` at the root.

## Commands

```bash
composer test                                 # run PHPUnit (uses simple-phpunit bridge)
vendor/bin/simple-phpunit Tests/Filter/Doctrine/ORMQueryBuilderUpdaterTest.php   # single file
vendor/bin/simple-phpunit --filter testBuildQuery                                # single test

vendor/bin/ecs check                          # coding-standards check (PSR-12 + ordered imports + short arrays)
vendor/bin/ecs check --fix                    # auto-fix (there is no Makefile here: no `make phpcsfix`)
vendor/bin/rector process --dry-run           # preview Rector changes (CI runs this in dry-run and fails on diff)
vendor/bin/rector process                     # apply Rector
```

`ecs` and `rector` are already in `require-dev`; `composer install` runs directly on the host (no Docker, no frontend toolchain in this repo).

CI (`.github/workflows/`):
- `ci.yml` — PHP 8.1/Symfony 5.4, 8.1/6.4, 8.2/7.4, 8.4/8.0. Any change must stay compatible with all four. The Symfony version is selected via the `SYMFONY_REQUIRE` env var and Flex.
- `coding-standards.yml` — ECS **and** Rector `--dry-run` on PHP 8.3. A Rector diff fails the build, so run `rector process --dry-run` before pushing.
- `bc-check.yml` — Roave BC check, but its `paths` filter is `lib/**`, a directory that does not exist in this repo. **The BC check therefore never actually runs on source changes.** Don't rely on it to catch a broken public signature; check by hand.

## Architecture

### The pipeline
`FilterBuilderUpdater::addFilterConditions()` orchestrates three event-driven stages:

1. **PREPARE** — dispatches `PrepareEvent` (`spiriit_filter.prepare`). `PrepareListener` inspects the supplied query builder and instantiates the matching `QueryInterface` wrapper. The `$queryClasses` map in that listener currently holds a single entry (`Doctrine\ORM\QueryBuilder` → `ORMQuery`): only Doctrine ORM is supported as of v12 (DBAL was removed — see `CHANGELOG-12.0.md`; MongoDB/ODM in v10 — see `CHANGELOG-10.0.md`). `PrepareListener` also carries the `force_case_insensitivity` / `encoding` config, which it forwards to `ORMQuery` → `ORMExpressionBuilder`.
2. **GET FILTER** — for each form field, dispatches `GetFilterConditionEvent` named `spiriit_form_filter.apply.<part>.<block_prefix>` (`<part>` is `ORMQuery::getEventPartName()` = `orm`). `DoctrineORMSubscriber::getSubscribedEvents()` maps each event to a handler that builds a `Condition` (DQL fragment + parameters). It listens both to the bundle's own block prefixes (`filter_text`, `filter_number`, `filter_entity`, `filter_date_range`, …) **and** to plain Symfony types (`text`, `email`, `integer`, `choice`, `entity`, `date`, `checkbox`, …), so a filter form built from core Symfony types still produces conditions.
3. **APPLY** — dispatches `ApplyFilterConditionEvent` (`spiriit_filter.apply_filters.orm`). `DoctrineApplyFilterListener` walks the `ConditionBuilder` tree (AND/OR nodes) and calls `andWhere`/`orWhere`/`where` on the query builder based on the `where_method` config.

Customising via the form's `apply_filter` option short-circuits step 2 (closure called directly, or a string used as the event suffix). Returning `false` from a closure disables the field.

### Where the filter handlers actually live
Handler methods are named `filterXxx()` — **not** `onFilterXxx()` — and several events share one handler (every simple scalar type points at `filterValue`).

`DoctrineORMSubscriber` is thin: `getSubscribedEvents()`, `filterEntity()` and `getEntityIdentifier()`. **Every other handler** (`filterValue`, `filterBoolean`, `filterCheckbox`, `filterDate`, `filterDateRange`, `filterDateTime`, `filterDateTimeRange`, `filterNumber`, `filterNumberRange`, `filterText`, `filterEnum`) is inherited from `AbstractDoctrineSubscriber`. Put a driver-agnostic handler in the abstract class and only ORM-specific ones (anything needing the `EntityManager`) in `DoctrineORMSubscriber`.

### Key collaborators
- `FilterBuilderUpdater` — public entry point; recursive `addFilters()` handles embedded types, collections, joins, and `inherit_data` forms.
- `FilterBuilderExecuter` (+ `RelationsAliasBag`) — tracks join aliases when the form declares relations via the `add_shared` attribute (a closure that calls `$qbe->addOnce(...)` to add joins idempotently).
- `EmbeddedFilterTypeInterface` — marker for form types that represent an embeddable / nested object (descend into children with a derived alias).
- `CollectionAdapterFilterType` — wraps a Symfony `CollectionType` so the bundle filters on the inner prototype instead of treating each row as a value.
- `ConditionBuilder` / `ConditionNode` — fluent AND/OR tree built per form via the `filter_condition_builder` option; default is a flat AND over all fields.
- `FilterTypeExtension` — the `FormType` extension that declares the bundle's form options (`apply_filter`, `data_extraction_method`, `filter_condition_builder`, `filter_field_name`, `filter_shared_name`) and copies them onto form **attributes**, which is where `FilterBuilderUpdater` reads them (`$child->getConfig()->getAttribute(...)`). Adding a new bundle-wide form option means touching this class.
- `FilterOperands` — operand constants: `OPERATOR_*` are DQL-ish strings (`eq`, `gt`, `gte`, `lt`, `lte`), `STRING_*` are **ints** (`STRING_STARTS = 1`, …). The `text.starts` / `text.contains` style strings only exist in bundle *configuration*; `FilterOperands::getStringOperandByString()` converts them to the int constants.

### Data extraction
`FormDataExtractor` holds the extraction methods; `FormDataExtractorPass` collects every service tagged `spiriit_form_filter.data_extraction_method` and calls `addMethod()` with it. The tag carries **no** attributes — the lookup key is whatever the method's own `getName()` returns.

Trap: the service ids and the method names disagree. `spiriit_form_filter.data_extraction_method.key_values` is `ValueKeysExtractionMethod`, whose `getName()` returns **`value_keys`**. The values usable in the form's `data_extraction_method` option are `default`, `text`, `value_keys` (the range types default to and only allow `value_keys`).

### DI configuration
`SpiriitFormFilterExtension` always loads `services.yaml` and `form.yaml`; `doctrine_orm.yaml` is loaded only when `listeners.doctrine_orm` is true (default). Config keys (`Configuration.php`): `listeners.doctrine_orm`, `where_method` (`and`/`or`/null), `condition_pattern` (default `text.starts`), `force_case_insensitivity`, `encoding`. `where_method` and `condition_pattern` become container parameters consumed by the apply listener and `TextFilterType`; the last two are pushed onto `spiriit_form_filter.filter_prepare` as method calls.

### Built-in filter types
Filter types live in `Filter/Form/Type/`. They typically extend a Symfony core type and set the bundle-specific attributes. Registration is uneven, so check where the type belongs before adding one:
- `FilterExtension::loadTypes()` instantiates the types with no dependencies. This is also the list the **test** `FormFactory` can resolve (`TestCase::getFormFactory()` builds a `FormRegistry` from `CoreExtension` + `FilterExtension`) — a type missing here cannot be used in a test, even though a real Symfony app would resolve it by FQCN.
- `EntityFilterType` is registered in `doctrine_orm.yaml` with the `form.type` tag because it needs `@doctrine`.
- `EnumFilterType` is registered nowhere and relies on FQCN autoloading in a real app.

When adding a new built-in filter type:
1. Register it in `FilterExtension::loadTypes()` (or via the `form.type` tag if it needs DI).
2. Add a matching `filterXxx()` handler in `AbstractDoctrineSubscriber` (or `DoctrineORMSubscriber` if it needs the `EntityManager`) and wire its block prefix in `DoctrineORMSubscriber::getSubscribedEvents()`.
3. Add a constant to `FilterOperands` if it introduces a new operand.

## Conventions specific to this repo

- **No `src/` directory.** All production code is at the repo root under the bundle namespace. Tests live under `Tests/`.
- **Tests use `testFoo()` naming**, not `it_should_*` — stay consistent with the surrounding file.
- `Tests/Filter/Doctrine/DoctrineQueryBuilderUpdater.php` is an **abstract base class** (no `Test` suffix, never run on its own) extended by `ORMQueryBuilderUpdaterTest` — a leftover of the era when ORM/DBAL/ODM shared the same suite. Shared assertions go there; ORM-specific ones in the concrete test.
- Tests bootstrap an in-memory SQLite EntityManager (`TestCase::getSqliteEntityManager()`, attribute mapping over `Tests/Fixtures/Entity`); the container is built manually in `TestCase::createContainerBuilder()` rather than via `KernelTestCase` — there is no test kernel. `initQueryBuilderUpdater()` is the usual entry point in a test.
- Public signatures are effectively unguarded in CI (see the BC-check note above). A deliberate break must be documented in a `CHANGELOG-X.0.md`.

## Known dead / stale code

Don't treat these as live API when refactoring, and don't "fix" them without asking:
- `Filter/Doctrine/Expression/DBALExpressionBuilder.php` — nothing references it since DBAL was dropped in v12; only `ORMExpressionBuilder` is used.
- `Filter/Form/Type/DocumentFilterType.php` — imports `Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType`, a class no longer available since MongoDB support was removed in v10.
- `.php_cs` at the root — superseded by `ecs.php`.
- `FilterTypeExtension::getExtendedType()` — kept alongside the static `getExtendedTypes()` that Symfony actually uses.
- `autoload.php.dist` still registers `AnnotationRegistry`, although mapping is attribute-based.
