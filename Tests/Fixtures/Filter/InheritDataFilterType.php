<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form filter for tests.
 *
 * @author Bart Heyrman <bartheyrman22@gmail.com>
 */
class InheritDataFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('item', ItemFilterType::class, [
                'add_shared' => function (FilterBuilderExecuterInterface $qbe): void {
                    $closure = function (QueryBuilder $filterBuilder, string $alias, string $joinAlias, Expr $expr): void {
                        $filterBuilder->leftJoin($alias . '.item', $joinAlias);
                    };


                    $qbe->addOnce($qbe->getAlias() . '.item', 'item', $closure);
                },
                'data_class' => Item::class,
            ])
            ->add('option', OptionFilterType::class, [
                'inherit_data' => true
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'inherit_filter';
    }
}
