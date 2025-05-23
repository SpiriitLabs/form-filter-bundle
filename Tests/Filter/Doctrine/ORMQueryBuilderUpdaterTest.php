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

use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionBuilderInterface;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Options;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\InheritDataFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemEmbeddedOptionsFilterType;

/**
 * Filter query builder tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ORMQueryBuilderUpdaterTest extends DoctrineQueryBuilderUpdater
{
    public function testBuildQuery(): void
    {
        parent::createBuildQueryTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\'', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position AND i.enabled = :p_i_enabled', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'blabla\' AND i.position > :p_i_position AND i.enabled = :p_i_enabled', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%blabla\' AND i.position <= :p_i_position AND i.createdAt = :p_i_createdAt', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%blabla\' AND i.position <= :p_i_position AND i.createdAt = :p_i_createdAt']);
    }

    public function testDisabledFieldQuery(): void
    {
        parent::createDisabledFieldTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.position > :p_i_position']);
    }

    public function testApplyFilterOption(): void
    {
        parent::createApplyFilterOptionTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name <> \'blabla\' AND i.position <> 2']);
    }

    public function testNumberRange(): void
    {
        parent::createNumberRangeTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.position > :p_i_position_left AND i.position < :p_i_position_right']);
    }

    public function testNumberRangeWithSelector(): void
    {
        parent::createNumberRangeCompoundTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.position_selector > :p_i_position_selector_left AND i.position_selector <= :p_i_position_selector_right']);
    }

    public function testNumberRangeDefaultValues(): void
    {
        parent::createNumberRangeDefaultValuesTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.default_position >= :p_i_default_position_left AND i.default_position <= :p_i_default_position_right']);
    }

    public function testDateRange(): void
    {
        parent::createDateRangeTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.createdAt <= \'2012-05-22 23:59:59\' AND i.createdAt >= \'2012-05-12 00:00:00\'']);
    }

    public function testDateRangeWithTimezone(): void
    {
        parent::createDateRangeWithTimezoneTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.startAt <= \'2015-10-20 18:59:59\' AND i.startAt >= \'2015-10-19 19:00:00\'', 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.startAt <= \'2015-10-16 18:59:59\' AND i.startAt >= \'2015-09-30 19:00:00\'']);
    }

    public function testDateTimeRange(): void
    {
        parent::createDateTimeRangeTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.updatedAt <= \'2012-06-10 22:12:00\' AND i.updatedAt >= \'2012-05-12 14:55:00\'']);
    }

    public function testFilterStandardType(): void
    {
        parent::createFilterStandardTypeTest('getDQL', ['SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i WHERE i.name LIKE \'%hey dude%\' AND i.position = 99']);
    }

    public function testEmbedFormFilter(): void
    {
        // doctrine query builder without any joins
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'dude', 'options' => [['label' => 'color', 'rank' => 3]]]);

        $expectedDql = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE i.name LIKE \'dude\' AND (opt.label LIKE \'color\' AND opt.rank = :p_opt_rank)';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(['p_opt_rank' => 3], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // doctrine query builder with joins
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $doctrineQueryBuilder->leftJoin('i.options', 'o');
        $form->submit(['name' => 'dude', 'options' => [['label' => 'size', 'rank' => 5]]]);

        $expectedDql = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options o WHERE i.name LIKE \'dude\' AND (o.label LIKE \'size\' AND o.rank = :p_o_rank)';

        $filterQueryBuilder->setParts(['i.options' => 'o']);
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(['p_o_rank' => 5], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    public function testCustomConditionBuilder(): void
    {
        // doctrine query builder without any joins + custom condition builder
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class, null, ['filter_condition_builder' => function (ConditionBuilderInterface $builder): void {
            $builder
                ->root('or')
                    ->field('options.label')
                    ->andX()
                        ->field('options.rank')
                        ->field('name')
                    ->end()
                ->end()
            ;
        }]);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'dude', 'options' => [['label' => 'color', 'rank' => 6]]]);

        $expectedDql = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE opt.label LIKE \'color\' OR (opt.rank = :p_opt_rank AND i.name LIKE \'dude\')';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(['p_opt_rank' => 6], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // doctrine query builder without any joins + custom condition builder
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class, null, ['filter_condition_builder' => function (ConditionBuilderInterface $builder): void {
            $builder
                ->root('and')
                    ->orX()
                        ->field('name')
                        ->field('options.label')
                    ->end()
                    ->orX()
                        ->field('options.rank')
                        ->field('position')
                    ->end()
                ->end()
            ;
        }]);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'dude', 'position' => 1, 'options' => [['label' => 'color', 'rank' => 6]]]);

        $expectedDql = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE (i.name LIKE \'dude\' OR opt.label LIKE \'color\') AND (opt.rank = :p_opt_rank OR i.position = :p_i_position)';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(['p_opt_rank' => 6, 'p_i_position' => 1], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    public function testWithDataClass(): void
    {
        // doctrine query builder without any joins + a data_class
        $form = $this->formFactory->create(ItemEmbeddedOptionsFilterType::class, null, ['data_class' => Item::class]);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'dude', 'options' => [['label' => 'color', 'rank' => 6]]]);

        $expectedDql = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE i.name LIKE \'dude\' AND (opt.label LIKE \'color\' AND opt.rank = :p_opt_rank)';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals(['p_opt_rank' => 6], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    public function testWithInheritDataFormOption(): void
    {
        // doctrine query builder without any joins + a data_class
        $form = $this->formFactory->create(InheritDataFilterType::class, null, ['data_class' => Options::class]);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder(Options::class, 'o');

        $form->submit(['option' => ['label' => 'dude', 'rank' => 1], 'item' => ['name' => 'blabla', 'position' => 2, 'enabled' => 'y']]);

        $expectedDql = 'SELECT o FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Options o LEFT JOIN o.item item';
        $expectedDql .= ' WHERE o.label LIKE \'dude\' AND o.rank = :p_o_rank AND (item.name LIKE \'blabla\' AND item.position > :p_item_position AND item.enabled = :p_item_enabled)';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
        $this->assertEquals([
            'p_o_rank' => 1.0,
            'p_item_position' => 2.0,
            'p_item_enabled' => true,
        ], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createDoctrineQueryBuilder(
        string $entityClassName = Item::class,
        string $alias = 'i'
    ): QueryBuilder {
        return $this->em
                     ->getRepository($entityClassName)
                     ->createQueryBuilder($alias);
    }
}
