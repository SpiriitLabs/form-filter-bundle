CHANGELOG 12.3
==============

- Added an opt-in persistence of the filter state (`Filter\State\`): the new `filter_persistence` form
  option makes `$form->handleRequest($request)` remember the state of a filter form and restore it on the
  requests that carry no filter data. See `Resources/doc/persistence.md`.
  - `FilterState` holds the view data of a filter form (strings and nested arrays only) and re-submits it,
    so entities, dates and enums are never serialized.
  - `FilterStateStorageInterface` with two implementations: `SessionFilterStateStorage`, which never
    starts a session (no persistence for a visitor that has none, and nothing at all on a `stateless`
    route), and `NullFilterStateStorage` to switch persistence off.
  - `FilterStateRequestHandler` decorates the `HttpFoundationRequestHandler`; it stores a valid submission,
    restores a stored state, drops it when the restoration turns out to be invalid, and honours the
    `_reset` query parameter (configurable through `persistence.reset_parameter`).
  - `FilterStateTypeExtension` declares the `filter_persistence` option; its `form.type_extension` tag has
    a `-10` priority so that `FormTypeHttpFoundationExtension` cannot overwrite the request handler.
  - `FilterUrlGenerator` builds a permalink from a state, and emits the reset parameter when the state is
    empty.
  - A filter form using `filter_persistence` must disable `csrf_protection`, otherwise the request handler
    throws a `LogicException`.
  - `TraceableFilterStateStorage` decorates `FilterStateStorageInterface` when `kernel.debug` is true, and
    feeds a **Persistence** section to the web profiler panel: the storage in use, the reset parameter and
    one row per `save`/`load`/`clear` with the persisted values. A save is read back from the storage
    rather than trusted, so a state a storage silently kept nothing of is reported `not_stored` instead of
    `saved` — and turns the toolbar yellow.
- Added `symfony/http-foundation` and `symfony/routing` to the `require` section (both were already
  installed transitively through `symfony/framework-bundle`).
