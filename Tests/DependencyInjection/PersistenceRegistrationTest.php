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
use Spiriit\Bundle\FormFilterBundle\Filter\Form\FilterStateTypeExtension;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateRequestHandler;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterUrlGenerator;
use Spiriit\Bundle\FormFilterBundle\Filter\State\SessionFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\PublicServicesPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Form\DependencyInjection\FormPass;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormFactoryInterface;

class PersistenceRegistrationTest extends TestCase
{
    private const RESET_PARAMETER = 'spiriit_form_filter.persistence.reset_parameter';

    public function testTheStateServicesAreRegistered(): void
    {
        $container = $this->loadExtension();

        $this->assertSame(SessionFilterStateStorage::class, $this->classOf($container, 'spiriit_form_filter.state.session_storage'));
        $this->assertSame(FilterStateRequestHandler::class, $this->classOf($container, 'spiriit_form_filter.state.request_handler'));
        $this->assertSame(FilterUrlGenerator::class, $this->classOf($container, 'spiriit_form_filter.state.url_generator'));
        $this->assertSame(FilterStateTypeExtension::class, $this->classOf($container, 'spiriit_form_filter.type_extension.state'));

        $this->assertSame('spiriit_form_filter.state.session_storage', (string) $container->getAlias(FilterStateStorageInterface::class));
        $this->assertSame('spiriit_form_filter.state.request_handler', (string) $container->getAlias(FilterStateRequestHandler::class));
        $this->assertSame('spiriit_form_filter.state.url_generator', (string) $container->getAlias(FilterUrlGenerator::class));
    }

    /**
     * A negative priority is what keeps FormTypeHttpFoundationExtension from overwriting our request handler.
     */
    public function testTheStateTypeExtensionRunsAfterTheHttpFoundationOne(): void
    {
        $tags = $this->loadExtension()->getDefinition('spiriit_form_filter.type_extension.state')->getTags();

        $this->assertSame([['extended_type' => FormType::class, 'priority' => -10]], $tags['form.type_extension']);
    }

    public function testTheResetParameterDefaultsToUnderscoreReset(): void
    {
        $this->assertSame('_reset', $this->loadExtension()->getParameter(self::RESET_PARAMETER));
    }

    public function testTheResetParameterIsSharedByTheHandlerAndTheUrlGenerator(): void
    {
        $container = $this->loadExtension(['persistence' => ['reset_parameter' => 'clear_filter']]);

        $this->assertSame('clear_filter', $container->getParameter(self::RESET_PARAMETER));
        $this->assertSame('%' . self::RESET_PARAMETER . '%', $container->getDefinition('spiriit_form_filter.state.request_handler')->getArgument(2));
        $this->assertSame('%' . self::RESET_PARAMETER . '%', $container->getDefinition('spiriit_form_filter.state.url_generator')->getArgument(1));
    }

    public function testAPersistedFormBuiltByTheContainerUsesTheStateRequestHandler(): void
    {
        $container = $this->createContainerBuilder();
        $container->compile();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $container->get('form.factory');
        $form = $formFactory->createNamed('item_filter', FormType::class, null, ['filter_persistence' => true]);

        $this->assertInstanceOf(FilterStateRequestHandler::class, $form->getConfig()->getRequestHandler());

        $plainForm = $formFactory->createNamed('item_filter', FormType::class);

        $this->assertInstanceOf(HttpFoundationRequestHandler::class, $plainForm->getConfig()->getRequestHandler());
    }

    private function classOf(ContainerBuilder $container, string $id): string
    {
        return $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadExtension(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new SpiriitFormFilterExtension();
        $extension->load([$config], $container);

        return $container;
    }

    private function createContainerBuilder(): ContainerBuilder
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
                'kernel.container_class' => 'PersistenceRegistrationTestContainer',
                'kernel.charset' => 'utf8',
                'kernel.runtime_environment' => 'test',
                'env(base64:default::SYMFONY_DECRYPTION_SECRET)' => 'dummy',
                'kernel.build_dir' => __DIR__,
                'container.build_id' => 'persistence-registration-test',
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
        $container->loadFromExtension('spiriit_form_filter', ['listeners' => ['doctrine_orm' => false]]);

        $container->addCompilerPass(new FormPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new PublicServicesPass(['form.factory']), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        return $container;
    }
}
