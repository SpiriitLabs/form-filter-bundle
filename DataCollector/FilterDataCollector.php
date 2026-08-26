<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\DataCollector;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Event\FilterAppliedEvent;
use Spiriit\Bundle\FormFilterBundle\Event\FilterEvents;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldExplanation;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FilterExplanation;
use Stringable;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Collects, for the web profiler, what each filter form did to its query builder.
 */
final class FilterDataCollector extends AbstractDataCollector implements EventSubscriberInterface
{
    public const NAME = 'spiriit_form_filter';

    /**
     * @var list<array<string, mixed>>
     */
    private array $runs = [];

    public static function getSubscribedEvents(): array
    {
        return [
            FilterEvents::APPLIED => 'onFilterApplied',
        ];
    }

    public function onFilterApplied(FilterAppliedEvent $event): void
    {
        $this->runs[] = $this->describeRun($event->getExplanation(), $event->getQueryBuilder());
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'runs' => $this->runs,
            'condition_count' => array_sum(array_column($this->runs, 'applied_count')),
            'warning_count' => array_sum(array_column($this->runs, 'no_listener_count')),
        ];
    }

    public function reset(): void
    {
        parent::reset();

        $this->runs = [];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public static function getTemplate(): string
    {
        return '@SpiriitFormFilter/Collector/filter.html.twig';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFilters(): array
    {
        return $this->data['runs'] ?? [];
    }

    public function getConditionCount(): int
    {
        return $this->data['condition_count'] ?? 0;
    }

    public function getWarningCount(): int
    {
        return $this->data['warning_count'] ?? 0;
    }

    public function hasWarnings(): bool
    {
        return $this->getWarningCount() > 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeRun(FilterExplanation $explanation, object $queryBuilder): array
    {
        $fields = [];
        $outcomes = [];

        foreach ($explanation->fields as $field) {
            $fields[] = $this->describeField($field);
            $outcomes[$field->name] = $field->outcome->value;
        }

        $isOrmQueryBuilder = $queryBuilder instanceof QueryBuilder;

        return [
            'form_name' => $explanation->formName,
            'form_type' => $explanation->formType,
            'form_type_short_name' => $this->shortName($explanation->formType),
            'root_alias' => $explanation->rootAlias,
            'fields' => $fields,
            'applied_count' => count($explanation->applied()),
            'no_listener_count' => count($explanation->withoutListener()),
            'has_warnings' => $explanation->hasWarnings(),
            'condition_tree' => $explanation->conditionTree instanceof ConditionNodeInterface
                ? $this->describeNode($explanation->conditionTree, $outcomes)
                : null,
            'joins' => $explanation->joins,
            'query_builder_class' => get_debug_type($queryBuilder),
            'dql' => $isOrmQueryBuilder ? $queryBuilder->getDQL() : null,
            'parameters' => $isOrmQueryBuilder ? $this->cloneVar($this->extractParameters($queryBuilder)) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeField(FieldExplanation $field): array
    {
        $condition = $field->condition;

        return [
            'path' => $field->path,
            'name' => $field->name,
            'form_type' => $field->formType,
            'form_type_short_name' => $this->shortName($field->formType),
            'block_prefix' => $field->blockPrefix,
            'field' => $field->field,
            'values' => $this->cloneVar($field->values),
            'event_name' => $field->eventName,
            'outcome' => $field->outcome->value,
            'expression' => $condition instanceof ConditionInterface ? $this->stringifyExpression($condition->getExpression()) : null,
            'parameters' => $condition instanceof ConditionInterface ? $this->cloneVar($condition->getParameters()) : null,
        ];
    }

    /**
     * @param  array<string, string> $outcomes
     * @return array{operator: string, fields: list<array<string, mixed>>, children: list<array<string, mixed>>}
     */
    private function describeNode(ConditionNodeInterface $node, array $outcomes): array
    {
        $fields = [];

        foreach ($node->getFields() as $name => $condition) {
            $fields[] = [
                'name' => $name,
                'outcome' => $outcomes[$name] ?? null,
                'expression' => $condition instanceof ConditionInterface ? $this->stringifyExpression($condition->getExpression()) : null,
            ];
        }

        $children = [];

        foreach ($node->getChildren() as $child) {
            $children[] = $this->describeNode($child, $outcomes);
        }

        return ['operator' => $node->getOperator(), 'fields' => $fields, 'children' => $children];
    }

    private function shortName(string $class): string
    {
        $separator = strrpos($class, '\\');

        return false === $separator ? $class : substr($class, $separator + 1);
    }

    private function stringifyExpression(mixed $expression): string
    {
        return is_string($expression) || $expression instanceof Stringable
            ? (string) $expression
            : get_debug_type($expression);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractParameters(QueryBuilder $queryBuilder): array
    {
        $parameters = [];

        foreach ($queryBuilder->getParameters() as $parameter) {
            if ($parameter instanceof Parameter) {
                $parameters[$parameter->getName()] = $parameter->getValue();
            }
        }

        return $parameters;
    }
}
