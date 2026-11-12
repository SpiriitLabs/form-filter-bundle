<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\BoundsType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

final class BoundsFilterTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAttribute('filter_options', [
            'whole_days' => self::hasDayGranularity($builder->get('from')->getType()),
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [BoundsType::class];
    }

    private static function hasDayGranularity(?ResolvedFormTypeInterface $type): bool
    {
        while (null !== $type) {
            if ($type->getInnerType() instanceof DateType) {
                return true;
            }

            $type = $type->getParent();
        }

        return false;
    }
}
