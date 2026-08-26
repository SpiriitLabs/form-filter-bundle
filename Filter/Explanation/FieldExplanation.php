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

use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;

/**
 * Describes how a single form field was turned (or not) into a filter condition.
 */
final class FieldExplanation
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly string $formType,
        public readonly string $blockPrefix,
        public readonly string $field,
        public readonly array $values,
        public readonly ?string $eventName,
        public readonly FieldOutcome $outcome,
        public readonly ?ConditionInterface $condition,
    ) {
    }

    public function isApplied(): bool
    {
        return FieldOutcome::Applied === $this->outcome;
    }

    public function isDisabled(): bool
    {
        return FieldOutcome::Disabled === $this->outcome;
    }

    public function hasListener(): bool
    {
        return null !== $this->eventName && FieldOutcome::NoListener !== $this->outcome;
    }
}
