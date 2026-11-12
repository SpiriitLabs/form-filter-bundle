CHANGELOG 12.4
==============

- Added support for Symfony 8.2's `BoundsType`: a `bounds` field filters with `>=` on its lower bound and `<=` on its
  upper bound, through `AbstractDoctrineSubscriber::filterBounds()`. A `DateType` upper bound covers the whole day,
  which requires its `input` option to be `datetime` or `datetime_immutable`.
  Bounds SQL cannot order the way the form does (enums, entities, booleans) throw a `LogicException`, use
  `apply_filter` for them. See `docs/features/provided-types.md`.
- Added `Filter\Doctrine\Bound` and the `BoundsFilterTypeExtension` form type extension.
- Dropped support for Symfony 5.4. The tested versions are now 6.4, 7.4, 8.1 and 8.2: Symfony 8.0 is EOL and Composer
  refuses to install it because of security advisories.
- Allowed `symfony/var-exporter` 8 in development: the test suite enables Doctrine ORM's native lazy objects on PHP 8.4.
- Replaced ECS with php-cs-fixer (`.php-cs-fixer.dist.php`), which also enforces the `spiriitlabs/form-filter-bundle`
  file header.
