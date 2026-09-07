<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\DataCollector;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\DataCollector\FilterDataCollector;
use Spiriit\Bundle\FormFilterBundle\Event\FilterEvents;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\NullFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Filter\State\SessionFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Filter\State\TraceableFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemEmbeddedOptionsFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

class FilterDataCollectorTest extends TestCase
{
    private EntityManager $em;

    private FilterDataCollector $collector;

    private FilterBuilderUpdaterInterface $updater;

    public function setUp(): void
    {
        parent::setUp();

        $container = $this->initContainer(true, ['spiriit_form_filter.data_collector']);

        $this->updater = $container->get('spiriit_form_filter.query_builder_updater');
        $this->collector = $container->get('spiriit_form_filter.data_collector');
        $this->em = $this->getSqliteEntityManager();
    }

    public function testCollectsOneRunPerAddFilterConditionsCall(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => '']);

        $this->updater->addFilterConditions($form, $queryBuilder);
        $this->collect();

        $this->assertCount(1, $this->collector->getFilters());
        $this->assertSame(1, $this->collector->getConditionCount());
        $this->assertFalse($this->collector->hasWarnings());

        $run = $this->collector->getFilters()[0];

        $this->assertSame('item_filter', $run['form_name']);
        $this->assertSame(ItemFilterType::class, $run['form_type']);
        $this->assertSame('i', $run['root_alias']);
        $this->assertSame(QueryBuilder::class, $run['query_builder_class']);
        $this->assertSame([], $run['joins']);
        $this->assertStringContainsString('i.name LIKE', $run['dql']);
        $this->assertSame([
            'name' => 'applied',
            'position' => 'no_condition',
            'enabled' => 'no_condition',
            'createdAt' => 'no_condition',
        ], $this->outcomes($run));

        $name = $run['fields'][0];

        $this->assertSame('item_filter.name', $name['path']);
        $this->assertSame('i.name', $name['field']);
        $this->assertSame('filter_text', $name['block_prefix']);
        $this->assertSame('TextFilterType', $name['form_type_short_name']);
        $this->assertSame('spiriit_form_filter.apply.orm.filter_text', $name['event_name']);
        $this->assertInstanceOf(Data::class, $name['values']);
        $this->assertStringContainsString('i.name LIKE', $name['expression']);

        $this->assertSame('and', $run['condition_tree']['operator']);
        $this->assertSame(
            ['name', 'position', 'enabled', 'createdAt'],
            array_column($run['condition_tree']['fields'], 'name')
        );
        $this->assertSame([], $run['condition_tree']['children']);
    }

    public function testDisabledFieldIsReportedAsDisabled(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class, null, ['disabled_name' => true]);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $this->updater->addFilterConditions($form, $queryBuilder);
        $this->collect();

        $run = $this->collector->getFilters()[0];

        $this->assertSame('disabled', $this->outcomes($run)['name']);
        $this->assertSame('applied', $this->outcomes($run)['position']);
        $this->assertNull($run['fields'][0]['event_name']);
        $this->assertNull($run['fields'][0]['expression']);
        $this->assertSame(['p_i_position' => 2.0], $run['parameters']->getValue(true));
    }

    public function testFieldWithoutListenerRaisesWarning(): void
    {
        $form = $this->formFactory->createNamedBuilder('item_filter', SymfonyFormType::class)
            ->add('name', TextFilterType::class)
            ->add('comment', TextareaType::class)
            ->getForm()
        ;
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'comment' => 'hello']);

        $this->updater->addFilterConditions($form, $queryBuilder);
        $this->collect();

        $run = $this->collector->getFilters()[0];

        $this->assertTrue($this->collector->hasWarnings());
        $this->assertSame(1, $this->collector->getWarningCount());
        $this->assertTrue($run['has_warnings']);

        $comment = $run['fields'][1];

        $this->assertSame('comment', $comment['name']);
        $this->assertSame('no_listener', $comment['outcome']);
        $this->assertSame('spiriit_form_filter.apply.orm.textarea', $comment['event_name']);
        $this->assertNull($comment['expression']);
        $this->assertStringNotContainsString('i.comment', $run['dql']);
    }

    public function testCollectsSeveralRuns(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['name' => 'blabla', 'position' => 2]);
        $this->updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class);
        $form->submit(['name' => 'dude', 'options' => [['label' => 'color', 'rank' => 3]]]);
        $this->updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $this->collect();

        $this->assertCount(2, $this->collector->getFilters());
        $this->assertSame(5, $this->collector->getConditionCount());
        $this->assertSame(['i.options' => 'opt'], $this->collector->getFilters()[1]['joins']);
    }

    public function testCollectWithoutAnyRun(): void
    {
        $this->collect();

        $this->assertSame([], $this->collector->getFilters());
        $this->assertSame(0, $this->collector->getConditionCount());
        $this->assertSame(0, $this->collector->getWarningCount());
        $this->assertFalse($this->collector->hasWarnings());
    }

    public function testResetForgetsRecordedRuns(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['name' => 'blabla']);
        $this->updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $this->collect();
        $this->assertCount(1, $this->collector->getFilters());

        $this->collector->reset();
        $this->collect();

        $this->assertSame([], $this->collector->getFilters());
        $this->assertSame(0, $this->collector->getConditionCount());
    }

    public function testCollectedDataIsSerializable(): void
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $queryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit([
            'name' => 'blabla',
            'position' => 2,
            'createdAt' => ['year' => 2012, 'month' => 5, 'day' => 12],
        ]);

        $this->updater->addFilterConditions($form, $queryBuilder);
        $this->collect();

        $unserialized = unserialize(serialize($this->collector));

        $this->assertInstanceOf(FilterDataCollector::class, $unserialized);
        $this->assertSame($this->collector->getConditionCount(), $unserialized->getConditionCount());
        $this->assertSame($this->collector->getFilters()[0]['dql'], $unserialized->getFilters()[0]['dql']);
        $this->assertInstanceOf(Data::class, $unserialized->getFilters()[0]['parameters']);
    }

    public function testMetadata(): void
    {
        $this->assertSame('spiriit_form_filter', $this->collector->getName());
        $this->assertSame('@SpiriitFormFilter/Collector/filter.html.twig', FilterDataCollector::getTemplate());
        $this->assertArrayHasKey(FilterEvents::APPLIED, FilterDataCollector::getSubscribedEvents());
    }

    public function testCollectsWhatTheStateStorageWasAskedToKeep(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());
        $collector = new FilterDataCollector($storage, '_reset');

        $storage->save($this->createState());
        $storage->load('item_filter');
        $storage->clear('item_filter');
        $storage->load('item_filter');

        $collector->collect(new Request(), new Response());

        $operations = $collector->getStateOperations();

        $this->assertSame(['saved', 'restored', 'cleared', 'nothing_stored'], array_column($operations, 'outcome'));
        $this->assertSame(['item_filter', 'item_filter', 'item_filter', 'item_filter'], array_column($operations, 'form_name'));
        $this->assertInstanceOf(Data::class, $operations[0]['values']);
        $this->assertNull($operations[2]['values']);

        $this->assertSame(1, $collector->getSavedCount());
        $this->assertSame(1, $collector->getRestoredCount());
        $this->assertSame(0, $collector->getNotStoredCount());
        $this->assertFalse($collector->hasStateWarnings());
        $this->assertSame(InMemoryFilterStateStorage::class, $collector->getStateStorageClass());
        $this->assertSame('_reset', $collector->getResetParameter());
    }

    public function testAStateTheStorageKeptNothingOfIsReportedAsAWarning(): void
    {
        $storage = new TraceableFilterStateStorage(new NullFilterStateStorage());
        $collector = new FilterDataCollector($storage);

        $storage->save($this->createState());

        $collector->collect(new Request(), new Response());

        $this->assertSame(['not_stored'], array_column($collector->getStateOperations(), 'outcome'));
        $this->assertSame(0, $collector->getSavedCount());
        $this->assertSame(1, $collector->getNotStoredCount());
        $this->assertTrue($collector->hasStateWarnings());
        $this->assertNull($collector->getResetParameter());
    }

    public function testNoStateIsCollectedWithoutAStorage(): void
    {
        $collector = new FilterDataCollector();

        $collector->collect(new Request(), new Response());

        $this->assertSame([], $collector->getStateOperations());
        $this->assertNull($collector->getStateStorageClass());
        $this->assertSame(0, $collector->getSavedCount());
        $this->assertFalse($collector->hasStateWarnings());
    }

    /**
     * The collector must watch the very storage the request handler was given.
     */
    public function testTheContainerTracesTheStorageUsedByTheRequestHandler(): void
    {
        $container = $this->initContainer(true, ['spiriit_form_filter.data_collector', 'spiriit_form_filter.state.request_handler']);
        $collector = $container->get('spiriit_form_filter.data_collector');

        $container->get('spiriit_form_filter.state.request_handler')
            ->handleRequest($this->formFactory->create(ItemFilterType::class), Request::create('/'))
        ;

        $collector->collect(new Request(), new Response());

        $this->assertSame(['nothing_stored'], array_column($collector->getStateOperations(), 'outcome'));
        $this->assertSame(SessionFilterStateStorage::class, $collector->getStateStorageClass());
    }

    private function createState(): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => ['name' => 'blabla']]);
    }

    private function collect(): void
    {
        $this->collector->collect(new Request(), new Response());
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, string>
     */
    private function outcomes(array $run): array
    {
        return array_combine(array_column($run['fields'], 'name'), array_column($run['fields'], 'outcome'));
    }

    private function createDoctrineQueryBuilder(string $entityClassName = Item::class, string $alias = 'i'): QueryBuilder
    {
        return $this->em
                    ->getRepository($entityClassName)
                    ->createQueryBuilder($alias)
        ;
    }
}
