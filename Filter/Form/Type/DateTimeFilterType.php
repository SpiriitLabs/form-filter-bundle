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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter type for datetime field.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DateTimeFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['required' => false, 'data_extraction_method' => 'default'])
            ->setAllowedValues('data_extraction_method', ['default'])
        ;
    }

    /**
     * @return ?string
     */
    public function getParent(): ?string
    {
        return DateTimeType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'filter_datetime';
    }
}
