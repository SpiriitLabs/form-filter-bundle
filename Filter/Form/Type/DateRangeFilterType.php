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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter type for date range field.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DateRangeFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('left_date', DateFilterType::class, $options['left_date_options']);
        $builder->add('right_date', DateFilterType::class, $options['right_date_options']);

        $builder->setAttribute('filter_value_keys', ['left_date' => $options['left_date_options'], 'right_date' => $options['right_date_options']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['required' => false, 'left_date_options' => [], 'right_date_options' => [], 'data_extraction_method' => 'value_keys'])
            ->setAllowedValues('data_extraction_method', ['value_keys'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_date_range';
    }
}
