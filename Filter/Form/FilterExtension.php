<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\SharedableFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Symfony\Component\Form\AbstractExtension;

/**
 * Load all filter types.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FilterExtension extends AbstractExtension
{
    /**
     * @return array
     */
    protected function loadTypes(): array
    {
        return [new BooleanFilterType(), new CheckboxFilterType(), new ChoiceFilterType(), new DateFilterType(), new DateRangeFilterType(), new DateTimeFilterType(), new DateTimeRangeFilterType(), new NumberFilterType(), new NumberRangeFilterType(), new TextFilterType(), new CollectionAdapterFilterType(), new SharedableFilterType()];
    }

    /**
     * {@inheritdoc}
     */
    public function loadTypeExtensions(): array
    {
        return [
            new FilterTypeExtension(),
        ];
    }
}
