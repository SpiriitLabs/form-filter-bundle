<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Event\FilterAppliedEvent;
use Spiriit\Bundle\FormFilterBundle\Event\FilterEvents;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldExplanation;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FieldOutcome;
use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FilterExplanation;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Options;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\FormType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\InheritDataFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemCallbackFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemEmbeddedOptionsFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;

/**
 * Explanation of what the updater did to the query builder.
 */
class ORMFilterExplanationTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp(): void
    {
        parent::setUp();

        $this->em = $this->getSqliteEntityManager();
    }

    public function testExplainsEveryFieldOutcome(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class, null, ['disabled_name' => true]);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $explanation = $this->explain($form, $queryBuilder);

        $this->assertCount(4, $explanation);
        $this->assertSame(['name', 'position', 'enabled', 'createdAt'], $this->names($explanation->fields));
        $this->assertSame('item_filter', $explanation->formName);
        $this->assertSame(ItemFilterType::class, $explanation->formType);
        $this->assertSame('i', $explanation->rootAlias);
        $this->assertSame([], $explanation->joins);

        [$name, $position, $enabled, $createdAt] = $explanation->fields;

        $this->assertSame(FieldOutcome::Disabled, $name->outcome);
        $this->assertTrue($name->isDisabled());
        $this->assertSame('item_filter.name', $name->path);
        $this->assertSame('name', $name->name);
        $this->assertSame('i.name', $name->field);
        $this->assertSame('filter_text', $name->blockPrefix);
        $this->assertNull($name->eventName);
        $this->assertNull($name->condition);

        $this->assertSame(FieldOutcome::Applied, $position->outcome);
        $this->assertTrue($position->isApplied());
        $this->assertSame('spiriit_form_filter.apply.orm.filter_number', $position->eventName);
        $this->assertSame('i', $position->values['alias']);
        $this->assertInstanceOf(ConditionInterface::class, $position->condition);
        $this->assertSame('position', $position->condition->getName());

        $this->assertSame(FieldOutcome::NoCondition, $enabled->outcome);
        $this->assertTrue($enabled->hasListener());
        $this->assertSame(FieldOutcome::NoCondition, $createdAt->outcome);
        $this->assertTrue($createdAt->hasListener());

        $this->assertCount(1, $explanation->applied());
        $this->assertFalse($explanation->hasWarnings());
        $this->assertSame($position->condition, $explanation->conditionTree->getFields()['position']);
        $this->assertSame(
            sprintf('SELECT i FROM %s i WHERE i.position > :p_i_position', Item::class),
            $queryBuilder->getDQL()
        );
    }

    public function testReportsFieldsWithoutListener(): void
    {
        $form = $this->formFactory->createBuilder(SymfonyFormType::class)
            ->add('description', TextareaType::class)
            ->getForm()
        ;
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['description' => 'x']);

        $explanation = $this->explain($form, $queryBuilder);

        $this->assertCount(1, $explanation);

        $description = $explanation->fields[0];

        $this->assertSame(FieldOutcome::NoListener, $description->outcome);
        $this->assertSame('spiriit_form_filter.apply.orm.textarea', $description->eventName);
        $this->assertFalse($description->hasListener());
        $this->assertNull($description->condition);

        $this->assertTrue($explanation->hasWarnings());
        $this->assertSame([$description], $explanation->withoutListener());
        $this->assertSame(sprintf('SELECT i FROM %s i', Item::class), $queryBuilder->getDQL());
    }

    public function testCallableFieldsHaveNoEventName(): void
    {
        $form = $this->formFactory->create(ItemCallbackFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => '']);

        $explanation = $this->explain($form, $queryBuilder);

        [$name, $position] = $explanation->fields;

        $this->assertSame(FieldOutcome::Applied, $name->outcome);
        $this->assertNull($name->eventName);
        $this->assertFalse($name->hasListener());

        $this->assertSame(FieldOutcome::NoCondition, $position->outcome);
        $this->assertNull($position->eventName);
        $this->assertNull($position->condition);
    }

    public function testStandardSymfonyTypesResolveBlockPrefixEvent(): void
    {
        $form = $this->formFactory->create(FormType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'hey dude', 'position' => 99]);

        $explanation = $this->explain($form, $queryBuilder);

        [$name, $position] = $explanation->fields;

        $this->assertSame(FieldOutcome::Applied, $name->outcome);
        $this->assertSame('my_form.name', $name->path);
        $this->assertSame('text', $name->blockPrefix);
        $this->assertSame('spiriit_form_filter.apply.orm.text', $name->eventName);

        $this->assertSame(FieldOutcome::Applied, $position->outcome);
        $this->assertNull($position->eventName);
    }

    public function testEmbeddedCollectionUsesJoinAlias(): void
    {
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'dude', 'options' => [['label' => 'color', 'rank' => 3]]]);

        $explanation = $this->explain($form, $queryBuilder);

        $this->assertSame(['i.options' => 'opt'], $explanation->joins);

        $label = $this->fieldNamed($explanation, 'options.label');

        $this->assertSame('item_filter.options.label', $label->path);
        $this->assertSame('opt.label', $label->field);
        $this->assertSame('opt', $label->values['alias']);
        $this->assertSame(FieldOutcome::Applied, $label->outcome);
        $this->assertSame($label->condition, $explanation->conditionTree->getChildren()[0]->getFields()['options.label']);
    }

    public function testEmbeddedCollectionReusesAnAlreadyDeclaredJoinAlias(): void
    {
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $queryBuilder->leftJoin('i.options', 'o');
        $form->submit(['name' => 'dude', 'options' => [['label' => 'size', 'rank' => 5]]]);

        $explanation = $this->explain($form, $queryBuilder, ['i.options' => 'o']);

        $this->assertSame(['i.options' => 'o'], $explanation->joins);
        $this->assertSame('o.label', $this->fieldNamed($explanation, 'options.label')->field);
    }

    public function testInheritDataFieldsKeepTheTreeName(): void
    {
        $form = $this->formFactory->create(InheritDataFilterType::class, null, ['data_class' => Options::class]);
        $queryBuilder = $this->createDoctrineQueryBuilder(Options::class, 'o');
        $form->submit([
            'option' => ['label' => 'dude', 'rank' => 1],
            'item' => ['name' => 'blabla', 'position' => 2, 'enabled' => 'y'],
        ]);

        $explanation = $this->explain($form, $queryBuilder);

        $this->assertSame('o', $explanation->rootAlias);
        $this->assertSame(['o.item' => 'item'], $explanation->joins);

        $itemName = $this->fieldNamed($explanation, 'item.name');
        $this->assertSame('inherit_filter.item.name', $itemName->path);
        $this->assertSame('item.name', $itemName->field);

        $label = $this->fieldNamed($explanation, 'label');
        $this->assertSame('inherit_filter.label', $label->path);
        $this->assertSame('o.label', $label->field);

        foreach ($explanation->applied() as $field) {
            $this->assertSame(
                $field->condition,
                $this->findCondition($explanation->conditionTree, $field->name),
                sprintf('The condition of "%s" is mapped on the tree under its own name.', $field->name)
            );
        }
    }

    public function testAppliedEventIsDispatchedAfterConditionsAreApplied(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla']);

        $container = $this->initContainer();
        $dispatcher = $container->get('event_dispatcher');

        $order = [];
        $dispatcher->addListener('spiriit_filter.apply_filters.orm', function () use (&$order): void {
            $order[] = 'apply_filters';
        });
        $dispatcher->addListener(FilterEvents::APPLIED, function (FilterAppliedEvent $event) use (&$order, $queryBuilder): void {
            $order[] = 'applied';

            $this->assertSame($queryBuilder, $event->getQueryBuilder());
            $this->assertStringContainsString('WHERE i.name LIKE \'blabla\'', $event->getQueryBuilder()->getDQL());
        });

        $container->get('spiriit_form_filter.query_builder_updater')->addFilterConditions($form, $queryBuilder);

        $this->assertSame(['apply_filters', 'applied'], $order);
    }

    public function testExplanationIsRebuiltOnEveryRun(): void
    {
        $container = $this->initContainer();
        $updater = $container->get('spiriit_form_filter.query_builder_updater');

        $explanations = [];
        $container->get('event_dispatcher')->addListener(
            FilterEvents::APPLIED,
            function (FilterAppliedEvent $event) use (&$explanations): void {
                $explanations[] = $event->getExplanation();
            }
        );

        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['name' => 'blabla']);
        $updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['position' => 5]);
        $updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $this->assertCount(2, $explanations);
        $this->assertCount(4, $explanations[1]);
        $this->assertSame(['name'], $this->names($explanations[0]->applied()));
        $this->assertSame(['position'], $this->names($explanations[1]->applied()));
    }

    /**
     * @param array<string, string> $parts
     */
    private function explain(FormInterface $form, QueryBuilder $queryBuilder, array $parts = []): FilterExplanation
    {
        $container = $this->initContainer();

        $captured = null;
        $container->get('event_dispatcher')->addListener(
            FilterEvents::APPLIED,
            function (FilterAppliedEvent $event) use (&$captured): void {
                $captured = $event->getExplanation();
            }
        );

        $updater = $container->get('spiriit_form_filter.query_builder_updater');

        if ([] !== $parts) {
            $updater->setParts($parts);
        }

        $updater->addFilterConditions($form, $queryBuilder);

        $this->assertInstanceOf(FilterExplanation::class, $captured);

        return $captured;
    }

    private function fieldNamed(FilterExplanation $explanation, string $name): FieldExplanation
    {
        foreach ($explanation as $field) {
            if ($name === $field->name) {
                return $field;
            }
        }

        $this->fail(sprintf('No field named "%s" in the explanation.', $name));
    }

    private function findCondition(ConditionNodeInterface $node, string $name): ?ConditionInterface
    {
        $fields = $node->getFields();

        if (isset($fields[$name]) && $fields[$name] instanceof ConditionInterface) {
            return $fields[$name];
        }

        foreach ($node->getChildren() as $child) {
            $condition = $this->findCondition($child, $name);

            if ($condition instanceof ConditionInterface) {
                return $condition;
            }
        }

        return null;
    }

    /**
     * @param  list<FieldExplanation> $fields
     * @return list<string>
     */
    private function names(array $fields): array
    {
        return array_map(static fn (FieldExplanation $field): string => $field->name, $fields);
    }

    private function createDoctrineQueryBuilder(string $entityClassName = Item::class, string $alias = 'i'): QueryBuilder
    {
        return $this->em
                    ->getRepository($entityClassName)
                    ->createQueryBuilder($alias)
        ;
    }
}
