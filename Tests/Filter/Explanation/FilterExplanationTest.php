<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Explanation;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\Condition;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNode;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldExplanation;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldOutcome;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FilterExplanation;

class FilterExplanationTest extends TestCase
{
    public function testAppliedReturnsOnlyAppliedFields(): void
    {
        $explanation = $this->createExplanation([
            $this->createField('name', FieldOutcome::Applied),
            $this->createField('position', FieldOutcome::NoCondition),
            $this->createField('enabled', FieldOutcome::Applied),
        ]);

        $this->assertSame(['name', 'enabled'], array_map(
            static fn (FieldExplanation $field): string => $field->name,
            $explanation->applied()
        ));
    }

    public function testWithoutListenerAndHasWarnings(): void
    {
        $explanation = $this->createExplanation([
            $this->createField('name', FieldOutcome::Applied),
            $this->createField('comment', FieldOutcome::NoListener),
        ]);

        $this->assertCount(1, $explanation->withoutListener());
        $this->assertSame('comment', $explanation->withoutListener()[0]->name);
        $this->assertTrue($explanation->hasWarnings());
    }

    public function testHasNoWarningWithoutAnyUnhandledField(): void
    {
        $explanation = $this->createExplanation([
            $this->createField('name', FieldOutcome::Applied),
            $this->createField('position', FieldOutcome::Disabled),
        ]);

        $this->assertSame([], $explanation->withoutListener());
        $this->assertFalse($explanation->hasWarnings());
    }

    public function testCountAndIteration(): void
    {
        $fields = [
            $this->createField('name', FieldOutcome::Applied),
            $this->createField('position', FieldOutcome::NoCondition),
        ];

        $explanation = $this->createExplanation($fields);

        $this->assertCount(2, $explanation);
        $this->assertSame($fields, iterator_to_array($explanation));
    }

    public function testByOutcomeReindexesResults(): void
    {
        $explanation = $this->createExplanation([
            $this->createField('name', FieldOutcome::NoCondition),
            $this->createField('position', FieldOutcome::Applied),
        ]);

        $applied = $explanation->byOutcome(FieldOutcome::Applied);

        $this->assertSame([0], array_keys($applied));
        $this->assertSame('position', $applied[0]->name);
    }

    public function testExposesTheFormAndQueryContext(): void
    {
        $tree = new ConditionNode('and');

        $explanation = new FilterExplanation(
            'item_filter',
            'App\Form\ItemFilterType',
            'i',
            [],
            $tree,
            ['i.options' => 'opt']
        );

        $this->assertSame('item_filter', $explanation->formName);
        $this->assertSame('App\Form\ItemFilterType', $explanation->formType);
        $this->assertSame('i', $explanation->rootAlias);
        $this->assertSame($tree, $explanation->conditionTree);
        $this->assertSame(['i.options' => 'opt'], $explanation->joins);
        $this->assertCount(0, $explanation);
    }

    /**
     * @param list<FieldExplanation> $fields
     */
    private function createExplanation(array $fields): FilterExplanation
    {
        return new FilterExplanation('item_filter', 'App\Form\ItemFilterType', 'i', $fields, null, []);
    }

    private function createField(string $name, FieldOutcome $outcome): FieldExplanation
    {
        return new FieldExplanation(
            path: 'item_filter.' . $name,
            name: $name,
            formType: 'App\Form\Type\SomeFilterType',
            blockPrefix: 'some_filter',
            field: 'i.' . $name,
            values: ['value' => 'x', 'alias' => 'i'],
            eventName: FieldOutcome::Applied === $outcome ? 'spiriit_form_filter.apply.orm.some_filter' : null,
            outcome: $outcome,
            condition: FieldOutcome::Applied === $outcome ? new Condition('i.' . $name . ' = :p') : null,
        );
    }
}
