<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\State;

/**
 * One thing a storage was asked to do with a filter state, and what came out of it.
 */
final class FilterStateOperation
{
    /**
     * @param array<string, mixed>|null $values
     */
    private function __construct(
        public readonly string $formName,
        public readonly FilterStateOutcome $outcome,
        public readonly ?array $values,
    ) {
    }

    public static function saved(FilterState $state, bool $stored): self
    {
        return new self($state->formName, $stored ? FilterStateOutcome::Saved : FilterStateOutcome::NotStored, $state->values);
    }

    public static function loaded(string $formName, ?FilterState $state): self
    {
        return new self($formName, null === $state ? FilterStateOutcome::NothingStored : FilterStateOutcome::Restored, $state?->values);
    }

    public static function cleared(string $formName): self
    {
        return new self($formName, FilterStateOutcome::Cleared, null);
    }
}
