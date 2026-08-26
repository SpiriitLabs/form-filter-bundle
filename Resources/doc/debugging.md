[9] Debugging filters
=====================

A filter form is silent by design: a field that produces no condition simply adds nothing to the query
builder. That is convenient until a filter "does nothing" and you have to guess why.

Every call to `FilterBuilderUpdater::addFilterConditions()` therefore builds a `FilterExplanation`: one
entry per walked field, telling you the DQL field it targeted, the extracted values, the event that was
dispatched and what came out of it.

i. Web profiler
---------------

When `kernel.debug` is true, the bundle registers a data collector. The web debug toolbar then shows the
number of applied conditions, and turns yellow as soon as a submitted field was silently ignored.

<!-- TODO: add a screenshot of the profiler panel here -->

The **Form filter** panel lists, for each `addFilterConditions()` call: the root alias, the joins declared
through the `add_shared` option, the condition tree, the resulting DQL with its bound parameters, and one
row per field with its outcome:

| Outcome | Meaning |
| --- | --- |
| `applied` | The field produced a condition, which was added to the condition tree. |
| `no_condition` | A listener (or an `apply_filter` callable) ran but returned nothing — usually an empty value. |
| `no_listener` | **No listener is registered for the event of this field**: the submitted value is silently ignored. |
| `disabled` | The field has `'apply_filter' => false`. |

### Fixing a `no_listener` field

The panel shows the event name that found no listener, for instance
`spiriit_form_filter.apply.orm.textarea`. Three ways to fix it:

* use one of the [provided filter types](provided-types.md) (`TextFilterType`, `NumberFilterType`, …)
  instead of the plain Symfony type;
* register your own listener on the event shown in the panel (see
  [Create your own filter type](working-with-the-bundle.md#v-create-your-own-filter-type));
* set the `apply_filter` option on the field to build the condition yourself.

### Disabling the collector

The collector is only registered when `kernel.debug` is true. To remove it in a debug environment as well,
drop its definition in a compiler pass:

```php
<?php
// src/DependencyInjection/Compiler/RemoveFilterCollectorPass.php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveFilterCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->removeDefinition('spiriit_form_filter.data_collector');
    }
}
```

ii. The spiriit_filter.applied event
------------------------------------

The explanation is published through the `spiriit_filter.applied` event
(`FilterEvents::APPLIED`), dispatched once per `addFilterConditions()` call, after the conditions have been
applied to the query builder. This is what the data collector listens to — and you can listen to it too,
for instance to log the fields nobody handled:

```php
<?php
// src/EventListener/FilterWarningListener.php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Spiriit\Bundle\FormFilterBundle\Event\FilterAppliedEvent;
use Spiriit\Bundle\FormFilterBundle\Event\FilterEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: FilterEvents::APPLIED)]
class FilterWarningListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(FilterAppliedEvent $event): void
    {
        $explanation = $event->getExplanation();

        foreach ($explanation->withoutListener() as $field) {
            $this->logger->warning('No listener for the filter field "{field}" (event "{event}").', [
                'field' => $field->path,
                'event' => $field->eventName,
            ]);
        }
    }
}
```

`FilterAppliedEvent` gives access to the query builder (`getQueryBuilder()`) and to the explanation
(`getExplanation()`).

`FilterExplanation` is countable and iterable over its fields, and exposes:

| Property or method | Description |
| --- | --- |
| `formName`, `formType` | Name and FQCN of the filter form. |
| `rootAlias` | Alias the conditions were built on. |
| `fields` | The `FieldExplanation` list, in the order the fields were walked. |
| `conditionTree` | The `ConditionNodeInterface` tree the conditions were mapped on. |
| `joins` | The `relation => alias` map built from the `add_shared` options. |
| `applied()`, `withoutListener()`, `byOutcome()` | Filter the fields by outcome. |
| `hasWarnings()` | True as soon as one field found no listener. |

`FieldExplanation` exposes:

| Property or method | Description |
| --- | --- |
| `path` | Complete field name, including the root form (`item_filter.options.label`). |
| `name` | Name the condition is mapped under in the condition tree (`options.label`). |
| `formType`, `blockPrefix` | FQCN and block prefix of the field type. |
| `field` | Targeted DQL field (`opt.label`). |
| `values` | Values extracted from the form, plus `alias` and the `filter_options`. |
| `eventName` | Dispatched event, or `null` when an `apply_filter` callable was used. |
| `outcome` | A `FieldOutcome` case: `Applied`, `NoCondition`, `NoListener` or `Disabled`. |
| `condition` | The produced `ConditionInterface`, or `null`. |
| `isApplied()`, `isDisabled()`, `hasListener()` | Shortcuts on the outcome. |
