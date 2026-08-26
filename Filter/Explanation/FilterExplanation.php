<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Explanation;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;

/**
 * Explains what a filter form did to a query builder.
 *
 * @implements IteratorAggregate<int, FieldExplanation>
 */
final class FilterExplanation implements Countable, IteratorAggregate
{
    /**
     * @param list<FieldExplanation> $fields
     * @param array<string, string>  $joins
     */
    public function __construct(
        public readonly string $formName,
        public readonly string $formType,
        public readonly string $rootAlias,
        public readonly array $fields,
        public readonly ?ConditionNodeInterface $conditionTree,
        public readonly array $joins,
    ) {
    }

    /**
     * @return list<FieldExplanation>
     */
    public function byOutcome(FieldOutcome $outcome): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (FieldExplanation $field): bool => $outcome === $field->outcome
        ));
    }

    /**
     * @return list<FieldExplanation>
     */
    public function applied(): array
    {
        return $this->byOutcome(FieldOutcome::Applied);
    }

    /**
     * @return list<FieldExplanation>
     */
    public function withoutListener(): array
    {
        return $this->byOutcome(FieldOutcome::NoListener);
    }

    public function hasWarnings(): bool
    {
        return [] !== $this->withoutListener();
    }

    public function count(): int
    {
        return count($this->fields);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->fields);
    }
}
