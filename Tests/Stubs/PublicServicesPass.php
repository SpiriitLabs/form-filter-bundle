<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Stubs;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes the given services public so a test can fetch them from a compiled container.
 */
final class PublicServicesPass implements CompilerPassInterface
{
    /**
     * @param list<string> $serviceIds
     */
    public function __construct(private readonly array $serviceIds)
    {
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->serviceIds as $id) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(true);
            }
        }
    }
}
