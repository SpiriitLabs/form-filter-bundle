<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Stubs;

use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;

final class RequiredBundleExtensions
{
    /**
     * @param class-string $bundleClass
     */
    public static function register(ContainerBuilder $container, string $bundleClass): void
    {
        if (!class_exists(RequiredBundle::class)) {
            return;
        }

        $bundle = new ReflectionClass($bundleClass);

        foreach ($bundle->getAttributes(RequiredBundle::class) as $attribute) {
            $requiredBundleClass = $attribute->newInstance()->class;

            if (!class_exists($requiredBundleClass)) {
                continue;
            }

            $requiredBundle = new $requiredBundleClass();
            $extension = $requiredBundle->getContainerExtension();

            if (null === $extension || $container->hasExtension($extension->getAlias())) {
                continue;
            }

            $container->registerExtension($extension);
            $container->loadFromExtension($extension->getAlias());
            self::register($container, $requiredBundleClass);
        }
    }
}
