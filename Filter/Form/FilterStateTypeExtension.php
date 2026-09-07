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

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds the "filter_persistence" option, which makes handleRequest() remember and restore the filter state.
 */
final class FilterStateTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly RequestHandlerInterface $requestHandler,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['filter_persistence']) {
            $builder->setRequestHandler($this->requestHandler);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['filter_persistence' => false]);
        $resolver->setAllowedTypes('filter_persistence', 'bool');
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
