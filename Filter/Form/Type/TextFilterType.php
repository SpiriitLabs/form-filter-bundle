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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter type for strings.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TextFilterType extends AbstractType
{
    /**
     * @var int
     */
    private $conditionPattern;

    /**
     * @param int $conditionPattern
     */
    public function __construct($conditionPattern = FilterOperands::STRING_EQUALS)
    {
        $this->conditionPattern = $conditionPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['compound']) {
            $builder->add('condition_pattern', ChoiceType::class, $options['choice_options']);
            $builder->add('text', TextType::class, $options['text_options']);
        } else {
            $builder->setAttribute('filter_options', ['condition_pattern' => $options['condition_pattern']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = ['required' => false, 'condition_pattern' => $this->conditionPattern, 'compound' => function (Options $options) {
            return $options['condition_pattern'] == FilterOperands::OPERAND_SELECTOR;
        }, 'text_options' => ['required' => false, 'trim' => true], 'choice_options' => ['choices' => FilterOperands::getStringOperandsChoices(), 'required' => false, 'translation_domain' => 'SpiriitFormFilterBundle'], 'data_extraction_method' => function (Options $options) {
            return $options['compound'] ? 'text' : 'default';
        }];

        if (version_compare(Kernel::VERSION, '3.1.0') < 0) {
            $defaults['choice_options']['choices_as_values'] = true; // must be removed for use in Symfony 3.1, needed for 2.8
        }

        $resolver
            ->setDefaults($defaults)
            ->setAllowedValues('data_extraction_method', ['text', 'default'])
            ->setAllowedValues('condition_pattern', FilterOperands::getStringOperands(true))
        ;
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_text';
    }
}
