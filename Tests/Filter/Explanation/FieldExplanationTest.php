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
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldExplanation;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldOutcome;

class FieldExplanationTest extends TestCase
{
    /**
     * @dataProvider outcomeProvider
     */
    public function testOutcomeQuestions(FieldOutcome $outcome, bool $isApplied, bool $isDisabled, bool $hasListener): void
    {
        $field = $this->createField($outcome, 'spiriit_form_filter.apply.orm.filter_text');

        $this->assertSame($isApplied, $field->isApplied());
        $this->assertSame($isDisabled, $field->isDisabled());
        $this->assertSame($hasListener, $field->hasListener());
    }

    /**
     * @return iterable<string, array{FieldOutcome, bool, bool, bool}>
     */
    public function outcomeProvider(): iterable
    {
        yield 'applied' => [FieldOutcome::Applied, true, false, true];
        yield 'no condition' => [FieldOutcome::NoCondition, false, false, true];
        yield 'no listener' => [FieldOutcome::NoListener, false, false, false];
        yield 'disabled' => [FieldOutcome::Disabled, false, true, true];
    }

    public function testAFieldHandledByACallableHasNoListener(): void
    {
        $field = $this->createField(FieldOutcome::Applied, null);

        $this->assertTrue($field->isApplied());
        $this->assertFalse($field->hasListener());
    }

    public function testExposesEverythingNeededToUnderstandTheField(): void
    {
        $condition = new Condition('i.name LIKE :p_i_name', ['p_i_name' => 'blabla']);

        $field = new FieldExplanation(
            path: 'item_filter.options.label',
            name: 'options.label',
            formType: 'App\Form\Type\TextFilterType',
            blockPrefix: 'filter_text',
            field: 'opt.label',
            values: ['value' => 'blabla', 'alias' => 'opt'],
            eventName: 'spiriit_form_filter.apply.orm.filter_text',
            outcome: FieldOutcome::Applied,
            condition: $condition,
        );

        $this->assertSame('item_filter.options.label', $field->path);
        $this->assertSame('options.label', $field->name);
        $this->assertSame('App\Form\Type\TextFilterType', $field->formType);
        $this->assertSame('filter_text', $field->blockPrefix);
        $this->assertSame('opt.label', $field->field);
        $this->assertSame(['value' => 'blabla', 'alias' => 'opt'], $field->values);
        $this->assertSame('spiriit_form_filter.apply.orm.filter_text', $field->eventName);
        $this->assertSame($condition, $field->condition);
    }

    private function createField(FieldOutcome $outcome, ?string $eventName): FieldExplanation
    {
        return new FieldExplanation(
            path: 'item_filter.name',
            name: 'name',
            formType: 'App\Form\Type\TextFilterType',
            blockPrefix: 'filter_text',
            field: 'i.name',
            values: ['value' => 'blabla', 'alias' => 'i'],
            eventName: $eventName,
            outcome: $outcome,
            condition: FieldOutcome::Applied === $outcome ? new Condition('i.name LIKE :p_i_name') : null,
        );
    }
}
