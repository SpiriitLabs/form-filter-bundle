<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\DependencyInjection\SpiriitFormFilterExtension;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdater;
use Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\Autowired;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AutowiringTest extends TestCase
{
    private static ?ContainerBuilder $container = null;

    public function testAutowiring(): void
    {
        $container = $this->createContainerBuilder([
            'framework' => [
                'secret' => 'test',
                'http_method_override' => false,
            ],
            'spiriit_form_filter' => []
        ]);

        $container
            ->register('autowired', Autowired::class)
            ->setPublic(true)
            ->setAutowired(true)
        ;

        $container->compile();

        $autowired = $container->get('autowired');

        $this->assertInstanceOf(FilterBuilderUpdater::class, $autowired->getFilterBuilderUpdater());
    }

    private static function createContainerBuilder(array $configs = []): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.bundles' => [
                    'FrameworkBundle' => FrameworkBundle::class,
                    'SpiriitFormFilterBundle' => SpiriitFormFilterBundle::class,
                ],
                'kernel.bundles_metadata' => [],
                'kernel.cache_dir' => __DIR__,
                'kernel.debug' => false,
                'kernel.environment' => 'test',
                'kernel.project_dir' => __DIR__,
                'kernel.share_dir' => __DIR__,
                'kernel.runtime_mode.web' => false,
                'kernel.container_class' => 'AutowiringTestContainer',
                'kernel.charset' => 'utf8',
                'kernel.runtime_environment' => 'test',
                'env(base64:default::SYMFONY_DECRYPTION_SECRET)' => 'dummy',
                'kernel.build_dir' => __DIR__,
                'debug.file_link_format' => null,
                'env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)' => true,
                'env(default::SYMFONY_TRUSTED_HOSTS)' => [],
                'env(default::SYMFONY_TRUSTED_PROXIES)' => [],
                'env(default::SYMFONY_TRUSTED_HEADERS)' => [],
            ]),
        );

        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new SpiriitFormFilterExtension());

        foreach ($configs as $extension => $config) {
            $container->loadFromExtension($extension, $config);
        }

        static::$container = $container;

        return $container;
    }
}
