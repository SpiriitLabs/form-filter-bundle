<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add extraction methods to the data extraction service.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FormDataExtractorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('spiriit_form_filter.form_data_extractor')) {
            $definition = $container->getDefinition('spiriit_form_filter.form_data_extractor');

            foreach ($container->findTaggedServiceIds('spiriit_form_filter.data_extraction_method') as $id => $attributes) {
                $definition->addMethodCall('addMethod', [new Reference($id)]);
            }
        }
    }
}
