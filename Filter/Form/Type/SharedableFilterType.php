<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form\Type;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter to used to dynamically add joins.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class SharedableFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // keep the closure as attribute to execute it later in the query builder updater
        $builder->setAttribute('add_shared', $options['add_shared']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['add_shared' => function (FilterBuilderExecuterInterface $qbe): void {
        }]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter_sharedable';
    }
}
