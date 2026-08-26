CHANGELOG 12.2
==============

- Added an "explain" mode: `FilterBuilderUpdater` now describes every field it walks
  (`Filter\Explanation\FilterExplanation`, `FieldExplanation`, `FieldOutcome`) and dispatches it through the new
  `spiriit_filter.applied` event (`FilterEvents::APPLIED`, `Event\FilterAppliedEvent`).
- Added a web profiler integration (`DataCollector\FilterDataCollector`, toolbar item + panel), registered only when
  `kernel.debug` is true. See `Resources/doc/debugging.md`.
- Added `RelationsAliasBag::all()` and the protected `FilterBuilderUpdater::explainField()`.
- `FilterBuilderUpdater::getFilterCondition()` now returns `null` when the `apply_filter` callable returns anything
  that is not a `ConditionInterface` (previously the raw value was passed through).
