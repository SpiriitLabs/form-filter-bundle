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

use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;

/**
 * Records the fields walked during a single addFilterConditions() call.
 *
 * @internal
 */
final class FilterExplanationBuilder
{
    /**
     * @var list<FieldExplanation>
     */
    private array $fields = [];

    public function __construct(
        private readonly string $formName,
        private readonly string $formType,
        private readonly string $rootAlias,
    ) {
    }

    public function add(FieldExplanation $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * @param array<string, string> $joins
     */
    public function build(?ConditionNodeInterface $conditionTree, array $joins): FilterExplanation
    {
        return new FilterExplanation(
            $this->formName,
            $this->formType,
            $this->rootAlias,
            $this->fields,
            $conditionTree,
            $joins
        );
    }
}
