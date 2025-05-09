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

use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter type for numbers.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class NumberFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['compound']) {
            // if the form is compound we don't need the NumberToLocalizedStringTransformer added in the parent type.
            $builder->resetViewTransformers();

            $builder->add('condition_operator', ChoiceType::class, $options['choice_options']);
            $builder->add('text', NumberType::class, $options['number_options']);
        } else {
            $builder->setAttribute('filter_options', ['condition_operator' => $options['condition_operator']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = ['required' => false, 'condition_operator' => FilterOperands::OPERATOR_EQUAL, 'compound' => function (Options $options): bool {
            return $options['condition_operator'] == FilterOperands::OPERAND_SELECTOR;
        }, 'number_options' => ['required' => false], 'choice_options' => ['choices' => FilterOperands::getNumberOperandsChoices(), 'required' => false, 'translation_domain' => 'SpiriitFormFilterBundle'], 'data_extraction_method' => function (Options $options): string {
            return $options['compound'] ? 'text' : 'default';
        }];

        if (version_compare(Kernel::VERSION, '3.1.0') < 0) {
            $defaults['choice_options']['choices_as_values'] = true; // must be removed for use in Symfony 3.1, needed for 2.8
        }

        $resolver
            ->setDefaults($defaults)
            ->setAllowedValues('data_extraction_method', ['text', 'default'])
            ->setAllowedValues('condition_operator', FilterOperands::getNumberOperands(true))
        ;
    }

    public function getParent(): ?string
    {
        return NumberType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_number';
    }
}
