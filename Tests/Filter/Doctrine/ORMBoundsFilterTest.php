<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Enum\Size;
use Symfony\Component\Form\Extension\Core\Type\BoundsType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;

class ORMBoundsFilterTest extends DoctrineQueryBuilderUpdater
{
    private const SELECT = 'SELECT i FROM Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item i';

    public function setUp(): void
    {
        if (!class_exists(BoundsType::class)) {
            $this->markTestSkipped('BoundsType requires symfony/form 8.2 or later.');
        }

        parent::setUp();
    }

    public function testBothBoundsAreInclusive(): void
    {
        $queryBuilder = $this->filter(
            $this->createBoundsForm('position', IntegerType::class),
            ['position' => ['from' => '1', 'to' => '3']],
        );

        $this->assertSame(self::SELECT . ' WHERE i.position >= :p_i_position_from AND i.position <= :p_i_position_to', $queryBuilder->getDQL());
        $this->assertSame(['p_i_position_from' => 1, 'p_i_position_to' => 3], $this->getQueryBuilderParameters($queryBuilder));
    }

    public function testHalfOpenRangeOnlyFiltersOnTheGivenBound(): void
    {
        $queryBuilder = $this->filter(
            $this->createBoundsForm('position', IntegerType::class),
            ['position' => ['from' => '', 'to' => '3']],
        );

        $this->assertSame(self::SELECT . ' WHERE i.position <= :p_i_position_to', $queryBuilder->getDQL());
    }

    public function testEmptyRangeAddsNoCondition(): void
    {
        $queryBuilder = $this->filter(
            $this->createBoundsForm('position', IntegerType::class),
            ['position' => ['from' => '', 'to' => '']],
        );

        $this->assertSame(self::SELECT, $queryBuilder->getDQL());
    }

    public function testDateUpperBoundCoversTheWholeDay(): void
    {
        $queryBuilder = $this->filter(
            $this->createBoundsForm('createdAt', DateType::class, ['widget' => 'single_text']),
            ['createdAt' => ['from' => '2012-05-12', 'to' => '2012-05-22']],
        );

        $this->assertSame(self::SELECT . ' WHERE i.createdAt >= :p_i_createdAt_from AND i.createdAt < :p_i_createdAt_to', $queryBuilder->getDQL());
        $this->assertEquals(
            ['p_i_createdAt_from' => new DateTime('2012-05-12'), 'p_i_createdAt_to' => new DateTime('2012-05-23')],
            $this->getQueryBuilderParameters($queryBuilder),
        );
    }

    public function testDateUpperBoundThatIsNotADateObjectIsRefused(): void
    {
        $form = $this->createBoundsForm('createdAt', DateType::class, ['widget' => 'single_text', 'input' => 'string']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A whole-day bound needs a date object, got "string"');

        $this->filter($form, ['createdAt' => ['from' => '', 'to' => '2012-05-22']]);
    }

    public function testDateTimeUpperBoundKeepsItsTime(): void
    {
        $queryBuilder = $this->filter(
            $this->createBoundsForm('updatedAt', DateTimeType::class, ['widget' => 'single_text']),
            ['updatedAt' => ['from' => '', 'to' => '2012-05-22T14:55']],
        );

        $this->assertSame(self::SELECT . ' WHERE i.updatedAt <= :p_i_updatedAt_to', $queryBuilder->getDQL());
        $this->assertEquals(['p_i_updatedAt_to' => new DateTime('2012-05-22 14:55')], $this->getQueryBuilderParameters($queryBuilder));
    }

    public function testBoundsThatSqlCannotOrderAreRefused(): void
    {
        $form = $this->createBoundsForm('name', EnumType::class, ['class' => Size::class]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A bound of type "Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Enum\Size" cannot be ordered in SQL');

        $this->filter($form, ['name' => ['from' => 'small', 'to' => 'large']]);
    }

    protected function createDoctrineQueryBuilder(): QueryBuilder
    {
        return $this->em->getRepository(Item::class)->createQueryBuilder('i');
    }

    private function createBoundsForm(string $field, string $type, array $options = []): FormInterface
    {
        return $this->formFactory->createBuilder()
            ->add($field, BoundsType::class, ['type' => $type, 'options' => $options])
            ->getForm();
    }

    private function filter(FormInterface $form, array $submittedData): QueryBuilder
    {
        $form->submit($submittedData);

        return $this->initQueryBuilderUpdater()->addFilterConditions($form, $this->createDoctrineQueryBuilder());
    }
}
