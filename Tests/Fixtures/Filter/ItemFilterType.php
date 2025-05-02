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

use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\SharedableFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form filter for tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['with_selector']) {
            $builder->add('name', TextFilterType::class, ['apply_filter' => $options['disabled_name'] ? false : null]);
            $builder->add('position', NumberFilterType::class, ['condition_operator' => FilterOperands::OPERATOR_GREATER_THAN]);
        } else {
            $builder->add('name', TextFilterType::class, ['condition_pattern' => FilterOperands::OPERAND_SELECTOR]);
            $builder->add('position', NumberFilterType::class, ['condition_operator' => FilterOperands::OPERAND_SELECTOR]);
        }

        $builder->add('enabled', $options['checkbox'] ? CheckboxFilterType::class : BooleanFilterType::class);
        $builder->add('createdAt', $options['datetime'] ? DateTimeFilterType::class : DateFilterType::class, [
            'widget' => 'choice'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['with_selector' => false, 'checkbox' => false, 'datetime' => false, 'disabled_name' => false]);
    }

    public function getParent(): string
    {
        return SharedableFilterType::class; // this allows us to use the "add_shared" option
    }
}
