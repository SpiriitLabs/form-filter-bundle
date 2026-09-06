---
description: Remember a filter across requests, share it as a permalink, or store it as a named preset.
---

# Remembering and sharing filters

By default the state of a filter form lives in the query string, and nowhere else. Open an item from the
list, come back, and the filter is gone. This chapter adds three things on top of that: remembering a
filter across requests, turning the current filter into a shareable permalink, and storing named filters.

Everything here is opt-in: without the `filter_persistence` option, the bundle never reads nor writes a
session.

## Remembering a filter

Set the `filter_persistence` option on the **root** filter form and keep using `handleRequest()`:

```php
<?php
// src/Form/Filter/ItemFilterType.php
namespace App\Form\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Filters\TextFilterType::class);
        $builder->add('position', Filters\NumberFilterType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'filter_persistence' => true,
        ]);
    }
}
```

The controller does not change:

```php
$form = $formFactory->create(ItemFilterType::class);
$form->handleRequest($request);

$filterBuilder = $em->getRepository(Item::class)->createQueryBuilder('i');
$filterBuilderUpdater->addFilterConditions($form, $filterBuilder);
```

What happens on each request:

| Request | Behaviour |
| --- | --- |
| carries filter data (`?item_filter[name]=foo`) | the form is submitted from the request; if it is valid, the state is stored |
| carries no filter data | the stored state is re-submitted to the form, as if the user had filled it again |
| carries the reset parameter (`?_reset`) | the stored state is dropped first, then the two rules above apply |

Two requirements, both enforced or strongly recommended:

* **`csrf_protection => false`** — a CSRF token cannot be replayed on a later request, so a restored state
  (or a permalink) would make the form invalid forever. The bundle throws a `LogicException` if CSRF
  protection is enabled on a persisted form. A filter is a read-only query: it does not need a token.
* **`method => GET`** — with a `POST` form, `handleRequest()` ignores the query string, so
  `?item_filter[...]` and the permalinks of the sharing section do nothing. A `POST` filter still works with
  persistence (it is what re-displays the filtered list after a post/redirect/get), it just cannot be
  shared through a URL.

Do **not** keep the manual submission some older examples show:

```php
// WRONG: handleRequest() has already submitted the form, this throws AlreadySubmittedException
$form->handleRequest($request);
$form->submit($request->query->get($form->getName()));
```

### What is stored

Only the **view data** of the form: strings and nested arrays, exactly what the browser sends. Entities,
`\DateTime` objects and enums are never serialized; the state is restored with `$form->submit()`, so every
data transformer runs again, the entity is reloaded from the database and the date is parsed anew.

A consequence: the stored values are JSON-safe, which is what makes the saved filters below possible.

## Resetting a filter

Any request carrying the `_reset` query parameter clears the stored state before anything else. Its
presence is enough, its value is ignored:

```twig
<a href="{{ path('item_list', {'_reset': ''}) }}">Clear filters</a>
```

The parameter name is configurable:

```yaml
# config/packages/spiriit_form_filter.yaml
spiriit_form_filter:
    persistence:
        reset_parameter: _reset
```

There is no way to express "no filter" with the form name itself: `?item_filter=` cannot be submitted to
a compound form, and a filter whose values are all empty simply disappears from the query string.

## Sharing a filter

`FilterUrlGenerator` turns the current state of a form into a URL:

```php
<?php
namespace App\Controller;

use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterUrlGenerator;

class ItemController
{
    public function __invoke(Request $request, FilterUrlGenerator $filterUrlGenerator): Response
    {
        // ... $form->handleRequest($request);

        $permalink = $filterUrlGenerator->generate('item_list', FilterState::fromForm($form));

        // reset the pagination, the restored filter probably has fewer pages
        $firstPage = $filterUrlGenerator->generate('item_list', FilterState::fromForm($form), ['page' => 1]);
    }
}
```

`generate()` takes the same arguments as the router — route, extra parameters, reference type — and nests
the filter values under the form name:

```
/items?item_filter[name]=foo&page=1
```

Two details worth knowing:

* the router percent-encodes the brackets, so the real URL reads
  `/items?item_filter%5Bname%5D=foo&page=1`;
* when no value is left in the state, the generator emits the reset parameter (`/items?_reset=`) instead
  of an empty filter. An empty filter would vanish from the query string, and the stored state of the
  visitor would be restored on the target page — the shared link would not show what its author saw.

## Saved filters

A named filter ("My inactive customers, Q3") belongs to your application: it has an owner, maybe sharing
rules and permissions. The bundle provides the serializable part, `FilterState`, and lets you store it.

```php
// save: your own entity, for instance SavedFilter { owner, name, formName, values (json) }
if ($form->isSubmitted() && $form->isValid()) {
    $saved = new SavedFilter($this->getUser(), $name, FilterState::fromForm($form)->toArray());
    $em->persist($saved);
    $em->flush();
}
```

```php
// apply: restore it, then store it so it survives the navigation like a hand-made filter
$state = FilterState::fromArray($saved->getState());

if ($state->isFor($form)) {
    $state->applyTo($form);
    $stateStorage->save($state);
}
```

`FilterState::toArray()` returns `['form' => 'item_filter', 'values' => [...]]`, ready for a JSON column.
`fromArray()` validates it and refuses anything that is not a string or an array. Scope the presets by
owner yourself, and only save a form that is submitted **and** valid — a preset that always errors is
worse than no preset. If the filter type changed since a preset was saved, see the drift entry in the
pitfalls below.

## Replacing the storage

The session storage is bound to `FilterStateStorageInterface`. Alias the interface to your own service to
store states elsewhere — a table, a cache pool, a user preference:

```yaml
# config/services.yaml
services:
    App\Filter\DoctrineFilterStateStorage: ~

    Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface:
        alias: App\Filter\DoctrineFilterStateStorage
```

```php
<?php
namespace App\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface;

class DoctrineFilterStateStorage implements FilterStateStorageInterface
{
    public function save(FilterState $state): void { /* ... */ }

    public function load(string $formName): ?FilterState { /* ... */ }

    public function clear(string $formName): void { /* ... */ }
}
```

To turn persistence off without touching the filter types — in tests, or on an API without a session —
alias the interface to `NullFilterStateStorage`:

```yaml
when@test:
    services:
        Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface:
            class: Spiriit\Bundle\FormFilterBundle\Filter\State\NullFilterStateStorage
```

## Pitfalls

In `dev`, the **Form filter** profiler panel shows what was saved, restored or dropped on each request —
including the states a storage silently kept nothing of. See
[Debugging filters](/features/debugging#the-persistence-section) before guessing.

### The bundle never starts a session

`SessionFilterStateStorage` reads and writes the session only when the visitor **already** sent one, and
only outside a `stateless` route. Touching a lazy session would set a cookie on anonymous visitors and
force `Cache-Control: private` on every listing page.

The consequence is explicit: a visitor without a session gets no persistence. Filters are still applied
from the query string, permalinks still work, nothing is remembered. That covers any back office, where
the user is logged in, and leaves a public listing cacheable.

### A logged-in user makes the listing private

The flip side: as soon as a session exists, reading it marks the response as `private` and, with the
file-based session handler, takes the session lock for the duration of the request. Do not enable
`filter_persistence` on a page you serve from a reverse proxy, or on a listing hammered by parallel AJAX
calls.

### One state per session, not per tab

The session holds a single state per form name. Filter in tab A, and page 2 opened in tab B uses the
filter of tab A. This is inherent to a session-backed storage: use permalinks (the sharing section above)
for anything that must be stable per tab or shared.

### Pagination

A restored filter may have fewer pages than the previous one, so `?page=7` can land out of bounds. Handle
it as you already handle a manually shrunk result set, and generate your links with
`FilterUrlGenerator::generate($route, $state, ['page' => 1])`.

### The same form name on two pages

The state is identified by `$form->getName()`, which comes from the block prefix. Two different filter
types therefore have two different keys, but the same type used on two pages shares one state. Give one
of them its own name:

```php
$form = $formFactory->createNamed('archived_item_filter', ItemFilterType::class);
```

### A field renamed or removed after a deployment

A stored state that no longer matches the form is self-healing: the restored form is invalid, the state is
dropped, and the next request starts clean. Without `symfony/validator` the obsolete key simply lands in
`$form->getExtraData()`, the form stays valid and the value is ignored — also harmless. The same applies
to a saved filter, except that this one is yours to delete.

### Localized numbers in a shared URL

A `NumberType` without `html5 => true` renders `1 234,5` in French and `1,234.5` in English, and the state
holds that rendered string. A permalink then breaks when the recipient uses another locale. On a filter
meant to be shared, prefer `html5 => true` and `grouping => false` on the numeric fields.

### `HEAD` requests

A `GET` form is not submitted from a `HEAD` request, so a `HEAD` on a listing silently restores the stored
state and stores nothing. Harmless, but worth knowing when reading access logs.
