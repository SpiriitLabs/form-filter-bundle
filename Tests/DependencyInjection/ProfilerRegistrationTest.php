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
use Spiriit\Bundle\FormFilterBundle\DataCollector\FilterDataCollector;
use Spiriit\Bundle\FormFilterBundle\DependencyInjection\SpiriitFormFilterExtension;
use Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\PublicServicesPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ProfilerRegistrationTest extends TestCase
{
    private const COLLECTOR_ID = 'spiriit_form_filter.data_collector';

    public function testCollectorDefinitionIsRegisteredInDebug(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => true]));

        $extension = new SpiriitFormFilterExtension();
        $extension->load([[]], $container);

        $this->assertTrue($container->hasDefinition(self::COLLECTOR_ID));

        $tags = $container->getDefinition(self::COLLECTOR_ID)->getTags();

        $this->assertSame([
            [
                'template' => '@SpiriitFormFilter/Collector/filter.html.twig',
                'id' => 'spiriit_form_filter',
                'priority' => 245,
            ],
        ], $tags['data_collector']);
        $this->assertArrayHasKey('kernel.event_subscriber', $tags);
        $this->assertSame([['method' => 'reset']], $tags['kernel.reset']);
    }

    public function testCollectorDefinitionIsAbsentWithoutDebug(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new SpiriitFormFilterExtension();
        $extension->load([[]], $container);

        $this->assertFalse($container->hasDefinition(self::COLLECTOR_ID));
    }

    public function testExtensionLoadsWhenKernelDebugParameterIsMissing(): void
    {
        $container = new ContainerBuilder();

        $extension = new SpiriitFormFilterExtension();
        $extension->load([[]], $container);

        $this->assertTrue($container->hasDefinition('spiriit_form_filter.query_builder_updater'));
        $this->assertFalse($container->hasDefinition(self::COLLECTOR_ID));
    }

    public function testCollectorIsInstantiableInCompiledDebugContainer(): void
    {
        $container = $this->createContainerBuilder(true);
        $container->compile();

        $this->assertInstanceOf(FilterDataCollector::class, $container->get(self::COLLECTOR_ID));
    }

    public function testCollectorIsNotRegisteredInCompiledProductionContainer(): void
    {
        $container = $this->createContainerBuilder(false);
        $container->compile();

        $this->assertFalse($container->has(self::COLLECTOR_ID));
    }

    private function createContainerBuilder(bool $debug): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.bundles' => [
                    'FrameworkBundle' => FrameworkBundle::class,
                    'SpiriitFormFilterBundle' => SpiriitFormFilterBundle::class,
                ],
                'kernel.bundles_metadata' => [],
                'kernel.cache_dir' => __DIR__,
                'kernel.debug' => $debug,
                'kernel.environment' => 'test',
                'kernel.project_dir' => __DIR__,
                'kernel.share_dir' => __DIR__,
                'kernel.runtime_mode.web' => false,
                'kernel.container_class' => 'ProfilerRegistrationTestContainer',
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

        $container->loadFromExtension('framework', [
            'secret' => 'test',
            'http_method_override' => false,
        ]);
        $container->loadFromExtension('spiriit_form_filter', []);

        $container->addCompilerPass(new PublicServicesPass([self::COLLECTOR_ID]), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        return $container;
    }
}
