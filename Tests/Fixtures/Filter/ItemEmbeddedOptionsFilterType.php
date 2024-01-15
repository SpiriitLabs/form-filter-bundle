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

use Doctrine\ORM\Query\Expr as ORMExpr;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form filter for tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ItemEmbeddedOptionsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $addShared = function (FilterBuilderExecuterInterface $qbe) {
            $joinClosure = function (QueryBuilder $filterBuilder, $alias, $joinAlias, ORMExpr $expr) {
                $filterBuilder->leftJoin($alias . '.options', $joinAlias);
            };
            $qbe->addOnce($qbe->getAlias() . '.options', 'opt', $joinClosure);
        };

        $builder->add('name', TextFilterType::class);
        $builder->add('position', NumberFilterType::class);
        $builder->add('options', CollectionAdapterFilterType::class, ['entry_type' => OptionFilterType::class, 'add_shared' => $addShared]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['doctrine_builder' => null]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'item_filter';
    }
}
