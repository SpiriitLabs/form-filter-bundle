<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\State;

use Closure;
use InvalidArgumentException;
use LogicException;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\SharedableFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\RangeFilterType;
use stdClass;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class FilterStateTest extends StateTestCase
{
    public function testFromArrayNormalizesValues(): void
    {
        $state = FilterState::fromArray(['form' => 'item_filter', 'values' => [
            'position' => 2,
            'ratio' => 1.5,
            'enabled' => true,
            'archived' => false,
            'name' => null,
            'label' => '',
            'colors' => [],
            'createdAt' => ['year' => 2024, 'month' => null],
        ]]);

        $this->assertSame('item_filter', $state->formName);
        $this->assertSame([
            'position' => '2',
            'ratio' => '1.5',
            'enabled' => '1',
            'label' => '',
            'colors' => [],
            'createdAt' => ['year' => '2024'],
        ], $state->values);
    }

    public function testFromArrayRejectsAnObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A filter state only holds strings and arrays, stdClass given at "createdAt[date]".');

        FilterState::fromArray(['form' => 'item_filter', 'values' => ['createdAt' => ['date' => new stdClass()]]]);
    }

    /**
     * @dataProvider invalidArrayProvider
     */
    public function testFromArrayRejectsAnInvalidShape(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        FilterState::fromArray($data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function invalidArrayProvider(): iterable
    {
        yield 'no form name' => [['values' => []]];
        yield 'empty form name' => [['form' => '', 'values' => []]];
        yield 'form name is not a string' => [['form' => 12, 'values' => []]];
        yield 'no values' => [['form' => 'item_filter']];
        yield 'values are not an array' => [['form' => 'item_filter', 'values' => 'blabla']];
    }

    public function testAStateSurvivesJsonEncoding(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $state = FilterState::fromForm($form);
        $decoded = FilterState::fromArray(json_decode(json_encode($state->toArray()), true));

        $this->assertSame($state->toArray(), $decoded->toArray());
    }

    public function testFromFormRejectsAChildForm(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A filter state can only be captured from a root form, "name" given.');

        FilterState::fromForm($form->get('name'));
    }

    public function testFromFormRejectsASingleFieldForm(): void
    {
        $form = $this->formFactory->createNamed('name', TextType::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A filter state can only be captured from a compound form, "name" is a single field.');

        FilterState::fromForm($form);
    }

    public function testApplyToRejectsAnotherForm(): void
    {
        $state = FilterState::fromArray(['form' => 'other_filter', 'values' => []]);
        $form = $this->formFactory->create(ItemFilterType::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This filter state belongs to form "other_filter", it cannot be applied to "item_filter".');

        $state->applyTo($form);
    }

    public function testIsForComparesTheFormName(): void
    {
        $state = FilterState::fromArray(['form' => 'item_filter', 'values' => []]);

        $this->assertTrue($state->isFor($this->formFactory->create(ItemFilterType::class)));
        $this->assertFalse($state->isFor($this->formFactory->createNamed('other_filter', FormType::class)));
    }

    public function testFromFormOnAnUnsubmittedFormDescribesTheEmptyForm(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class, null, ['with_selector' => true]);

        $this->assertSame([
            'name' => ['condition_pattern' => '', 'text' => ''],
            'position' => ['condition_operator' => '', 'text' => ''],
            'enabled' => '',
            'createdAt' => ['year' => '', 'month' => '', 'day' => ''],
        ], FilterState::fromForm($form)->values);
    }

    /**
     * @dataProvider roundTripProvider
     */
    public function testAStateResubmitsToTheSameData(Closure $build, array $payload, array $expectedValues): void
    {
        $submitted = $this->buildForm($build);
        $submitted->submit($payload);

        $this->assertTrue($submitted->isValid(), 'the payload of the data set is a valid submission');

        $state = FilterState::fromForm($submitted);
        $this->assertSame($expectedValues, $state->values);

        $restored = $this->buildForm($build);
        $state->applyTo($restored);

        $this->assertTrue($restored->isValid(), (string) $restored->getErrors(true));
        $this->assertEquals($submitted->getData(), $restored->getData());
        $this->assertSame($state->values, FilterState::fromForm($restored)->values);
    }

    /**
     * @return iterable<string, array{Closure, array<string, mixed>, array<string, mixed>}>
     */
    public function roundTripProvider(): iterable
    {
        yield 'text' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('name', TextType::class),
            ['name' => 'blabla'],
            ['name' => 'blabla'],
        ];

        yield 'integer' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('position', IntegerType::class),
            ['position' => '2'],
            ['position' => '2'],
        ];

        yield 'checkbox checked' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('enabled', CheckboxType::class, ['required' => false]),
            ['enabled' => '1'],
            ['enabled' => '1'],
        ];

        yield 'checkbox unchecked' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('enabled', CheckboxType::class, ['required' => false]),
            [],
            [],
        ];

        yield 'expanded single choice' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('color', ChoiceType::class, ['choices' => ['Red' => 'red', 'Blue' => 'blue'], 'expanded' => true, 'placeholder' => 'any', 'required' => false]),
            ['color' => 'red'],
            ['color' => [0 => 'red']],
        ];

        yield 'expanded multiple choice' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('colors', ChoiceType::class, ['choices' => ['Red' => 'red', 'Blue' => 'blue'], 'expanded' => true, 'multiple' => true, 'required' => false]),
            ['colors' => ['red', 'blue']],
            ['colors' => [0 => 'red', 1 => 'blue']],
        ];

        yield 'date as choices' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateType::class, ['widget' => 'choice', 'required' => false]),
            ['createdAt' => ['year' => '2024', 'month' => '3', 'day' => '9']],
            ['createdAt' => ['year' => '2024', 'month' => '3', 'day' => '9']],
        ];

        yield 'date as single text' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateType::class, ['widget' => 'single_text', 'required' => false]),
            ['createdAt' => '2024-03-09'],
            ['createdAt' => '2024-03-09'],
        ];

        yield 'time as choices' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('startAt', TimeType::class, ['widget' => 'choice', 'required' => false]),
            ['startAt' => ['hour' => '13', 'minute' => '21']],
            ['startAt' => ['hour' => '13', 'minute' => '21']],
        ];

        yield 'datetime as choices' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateTimeType::class, ['widget' => 'choice', 'required' => false]),
            ['createdAt' => ['date' => ['year' => '2024', 'month' => '3', 'day' => '9'], 'time' => ['hour' => '13', 'minute' => '21']]],
            ['createdAt' => ['date' => ['year' => '2024', 'month' => '3', 'day' => '9'], 'time' => ['hour' => '13', 'minute' => '21']]],
        ];

        // a compound view data would be a model representation here, and would break the child date field
        yield 'datetime mixing a single text date and choice times' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateTimeType::class, ['widget' => 'choice', 'date_widget' => 'single_text', 'required' => false]),
            ['createdAt' => ['date' => '2024-03-09', 'time' => ['hour' => '13', 'minute' => '21']]],
            ['createdAt' => ['date' => '2024-03-09', 'time' => ['hour' => '13', 'minute' => '21']]],
        ];

        yield 'datetime as single text' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateTimeType::class, ['widget' => 'single_text', 'required' => false]),
            ['createdAt' => '2024-03-09T13:21'],
            ['createdAt' => '2024-03-09T13:21'],
        ];

        yield 'repeated' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('name', RepeatedType::class, ['type' => TextType::class, 'required' => false]),
            ['name' => ['first' => 'blabla', 'second' => 'blabla']],
            ['name' => ['first' => 'blabla', 'second' => 'blabla']],
        ];

        yield 'collection' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('names', CollectionType::class, ['entry_type' => TextType::class, 'allow_add' => true]),
            ['names' => ['blabla', 'blibli']],
            ['names' => [0 => 'blabla', 1 => 'blibli']],
        ];

        yield 'filter_text' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('name', TextFilterType::class),
            ['name' => 'blabla'],
            ['name' => 'blabla'],
        ];

        yield 'filter_text with a pattern selector' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('name', TextFilterType::class, ['condition_pattern' => FilterOperands::OPERAND_SELECTOR]),
            ['name' => ['text' => 'blabla', 'condition_pattern' => FilterOperands::STRING_ENDS]],
            ['name' => ['condition_pattern' => (string) FilterOperands::STRING_ENDS, 'text' => 'blabla']],
        ];

        yield 'filter_number' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('position', NumberFilterType::class),
            ['position' => '2'],
            ['position' => '2'],
        ];

        yield 'filter_number with an operator selector' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('position', NumberFilterType::class, ['condition_operator' => FilterOperands::OPERAND_SELECTOR]),
            ['position' => ['text' => '2', 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL]],
            ['position' => ['condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL, 'text' => '2']],
        ];

        yield 'filter_number_range' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('position', NumberRangeFilterType::class),
            ['position' => ['left_number' => '1', 'right_number' => '3']],
            ['position' => ['left_number' => '1', 'right_number' => '3']],
        ];

        yield 'filter_boolean' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('enabled', BooleanFilterType::class),
            ['enabled' => BooleanFilterType::VALUE_YES],
            ['enabled' => BooleanFilterType::VALUE_YES],
        ];

        yield 'filter_checkbox' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('enabled', CheckboxFilterType::class),
            ['enabled' => 'yes'],
            ['enabled' => '1'],
        ];

        yield 'filter_choice' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('color', ChoiceFilterType::class, ['choices' => ['Red' => 'red', 'Blue' => 'blue']]),
            ['color' => 'red'],
            ['color' => 'red'],
        ];

        yield 'filter_date' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateFilterType::class, ['widget' => 'choice']),
            ['createdAt' => ['year' => '2024', 'month' => '3', 'day' => '9']],
            ['createdAt' => ['year' => '2024', 'month' => '3', 'day' => '9']],
        ];

        yield 'filter_date_range' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateRangeFilterType::class, ['left_date_options' => ['widget' => 'single_text'], 'right_date_options' => ['widget' => 'choice', 'years' => range(2010, 2030)]]),
            ['createdAt' => ['left_date' => '2012-05-12', 'right_date' => ['year' => '2012', 'month' => '5', 'day' => '22']]],
            ['createdAt' => ['left_date' => '2012-05-12', 'right_date' => ['year' => '2012', 'month' => '5', 'day' => '22']]],
        ];

        yield 'filter_datetime' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('createdAt', DateTimeFilterType::class, ['widget' => 'choice']),
            ['createdAt' => ['date' => ['year' => '2024', 'month' => '3', 'day' => '9'], 'time' => ['hour' => '13', 'minute' => '21']]],
            ['createdAt' => ['date' => ['year' => '2024', 'month' => '3', 'day' => '9'], 'time' => ['hour' => '13', 'minute' => '21']]],
        ];

        yield 'filter_datetime_range' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('updatedAt', DateTimeRangeFilterType::class, ['left_datetime_options' => ['date_widget' => 'single_text', 'time_widget' => 'single_text'], 'right_datetime_options' => ['date_widget' => 'choice', 'time_widget' => 'choice', 'years' => range(2010, 2030)]]),
            ['updatedAt' => ['left_datetime' => ['date' => '2012-05-12', 'time' => '14:55'], 'right_datetime' => ['date' => ['year' => '2012', 'month' => '5', 'day' => '22'], 'time' => ['hour' => '20', 'minute' => '30']]]],
            ['updatedAt' => ['left_datetime' => ['date' => '2012-05-12', 'time' => '14:55'], 'right_datetime' => ['date' => ['year' => '2012', 'month' => '5', 'day' => '22'], 'time' => ['hour' => '20', 'minute' => '30']]]],
        ];

        yield 'filter_collection_adapter' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('options', CollectionAdapterFilterType::class, ['entry_type' => TextFilterType::class]),
            ['options' => [0 => 'blabla']],
            ['options' => [0 => 'blabla']],
        ];

        yield 'filter_sharedable' => [
            fn (FormBuilderInterface $builder): FormBuilderInterface => $builder->add('options', SharedableFilterType::class),
            ['options' => []],
            ['options' => []],
        ];
    }

    /**
     * @dataProvider itemFilterPayloadProvider
     */
    public function testARestoredItemFilterBuildsTheSameQuery(array $options, array $payload): void
    {
        $submitted = $this->formFactory->create(ItemFilterType::class, null, $options);
        $submitted->submit($payload);

        $restored = $this->formFactory->create(ItemFilterType::class, null, $options);
        FilterState::fromForm($submitted)->applyTo($restored);

        $this->assertEquals($this->buildQuery($submitted), $this->buildQuery($restored));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public function itemFilterPayloadProvider(): iterable
    {
        $year = date('Y');

        yield 'text and number' => [[], ['name' => 'blabla', 'position' => 2]];
        yield 'boolean' => [[], ['name' => 'blabla', 'position' => 2, 'enabled' => BooleanFilterType::VALUE_YES]];
        yield 'checkbox' => [['checkbox' => true], ['name' => 'blabla', 'position' => 2, 'enabled' => 'yes']];
        yield 'date and selectors' => [['with_selector' => true], [
            'name' => ['text' => 'blabla', 'condition_pattern' => FilterOperands::STRING_ENDS],
            'position' => ['text' => 2, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL],
            'createdAt' => ['year' => $year, 'month' => 9, 'day' => 27],
        ]];
        yield 'datetime and selectors' => [['with_selector' => true, 'datetime' => true], [
            'name' => ['text' => 'blabla', 'condition_pattern' => FilterOperands::STRING_ENDS],
            'position' => ['text' => 2, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL],
            'createdAt' => ['date' => ['year' => $year, 'month' => 9, 'day' => 27], 'time' => ['hour' => 13, 'minute' => 21]],
        ]];
    }

    /**
     * @dataProvider rangeFilterPayloadProvider
     */
    public function testARestoredRangeFilterBuildsTheSameQuery(array $payload): void
    {
        $submitted = $this->formFactory->create(RangeFilterType::class);
        $submitted->submit($payload);

        $restored = $this->formFactory->create(RangeFilterType::class);
        FilterState::fromForm($submitted)->applyTo($restored);

        $this->assertEquals($this->buildQuery($submitted), $this->buildQuery($restored));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function rangeFilterPayloadProvider(): iterable
    {
        yield 'number range' => [['position' => ['left_number' => 1, 'right_number' => 3]]];
        yield 'number range with selectors' => [['position_selector' => [
            'left_number' => ['text' => 4, 'condition_operator' => FilterOperands::OPERATOR_GREATER_THAN],
            'right_number' => ['text' => 8, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL],
        ]]];
        yield 'date range' => [['createdAt' => ['left_date' => '2012-05-12', 'right_date' => ['year' => '2012', 'month' => '5', 'day' => '22']]]];
        yield 'date range with a view timezone' => [['startAt' => ['left_date' => '2015-10-01', 'right_date' => '2015-10-16']]];
        yield 'datetime range' => [['updatedAt' => [
            'left_datetime' => ['date' => '2012-05-12', 'time' => '14:55'],
            'right_datetime' => ['date' => ['year' => '2012', 'month' => '5', 'day' => '22'], 'time' => ['hour' => '20', 'minute' => '30']],
        ]]];
    }

    private function buildForm(Closure $build): FormInterface
    {
        $builder = $this->formFactory->createNamedBuilder('item_filter', FormType::class);
        $build($builder);

        return $builder->getForm();
    }
}
