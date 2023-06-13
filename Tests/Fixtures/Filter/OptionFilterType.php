<?php

namespace Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form filter for tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class OptionFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextFilterType::class);
        $builder->add('rank', NumberFilterType::class);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'options_filter';
    }
}
