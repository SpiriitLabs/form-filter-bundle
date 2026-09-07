<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Spiriit\Bundle\FormFilterBundle\DependencyInjection\Compiler\FormDataExtractorPass;
use Spiriit\Bundle\FormFilterBundle\DependencyInjection\SpiriitFormFilterExtension;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\FilterExtension;
use Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\PublicServicesPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    public function setUp(): void
    {
        $this->formFactory = $this->getFormFactory();
    }

    /**
     * Create a form factory instance.
     *
     * @return FormFactory
     */
    public function getFormFactory()
    {
        $resolvedFormTypeFactory = new ResolvedFormTypeFactory();

        $registery = new FormRegistry([new CoreExtension(), new FilterExtension()], $resolvedFormTypeFactory);

        return new FormFactory($registery, $resolvedFormTypeFactory);
    }

    /**
     * EntityManager object together with annotation mapping driver and
     * pdo_sqlite database in memory
     *
     * @return EntityManager
     */
    public function getSqliteEntityManager()
    {
        $arrayAdapter = new ArrayAdapter();
        $cache = new ArrayAdapter();

        $mappingDriver = new AttributeDriver([__DIR__ . '/Fixtures/Entity']);

        $config = ORMSetup::createAttributeMetadataConfiguration([]);

        $config->setMetadataDriverImpl($mappingDriver);
        $config->setMetadataCache($arrayAdapter);
        $config->setQueryCache($cache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName(ClassMetadataFactory::class);
        $config->setDefaultRepositoryClassName(EntityRepository::class);

        $connection = DriverManager::getConnection([
           'driver' => 'pdo_sqlite',
           'memory' => true
       ], $config);

        return new EntityManager($connection, $config);
    }

    protected function initQueryBuilderUpdater()
    {
        return $this->initContainer()->get('spiriit_form_filter.query_builder_updater');
    }

    /**
     * @param list<string> $publicServiceIds
     */
    protected function initContainer(bool $debug = false, array $publicServiceIds = []): ContainerBuilder
    {
        return self::createContainerBuilder([
            'framework' => [
                'secret' => 'test',
                'http_method_override' => true,
            ],
            'spiriit_form_filter' => [
                'listeners' => [
                    'doctrine_orm' => true,
                ]
            ],
        ], $debug, $publicServiceIds);
    }

    /**
     * @param list<string> $publicServiceIds
     */
    private static function createContainerBuilder(array $configs = [], bool $debug = false, array $publicServiceIds = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles' => [
                'FrameworkBundle' => FrameworkBundle::class,
                'DoctrineBundle' => DoctrineBundle::class,
                'SpiriitFormFilterBundle' => SpiriitFormFilterBundle::class
            ],
            'kernel.bundles_metadata' => [],
            'kernel.cache_dir' => __DIR__,
            'kernel.build_dir' => __DIR__,
            'kernel.debug' => $debug,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => __DIR__,
            'kernel.project_dir' => __DIR__,
            'kernel.container_class' => 'AutowiringTestContainer',
            'kernel.charset' => 'utf8',
            'env(base64:default::SYMFONY_DECRYPTION_SECRET)' => 'dummy',
            'debug.file_link_format' => null,
            'env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)' => true,
            'env(default::SYMFONY_TRUSTED_HOSTS)' => [],
            'env(default::SYMFONY_TRUSTED_PROXIES)' => [],
            'env(default::SYMFONY_TRUSTED_HEADERS)' => [],
        ]));

        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new SpiriitFormFilterExtension());

        $extension = new DoctrineExtension();
        $container->registerExtension($extension);
        $extension->load([[
            'dbal' => [
                'connections' => [
                    'default' => [
                        'driver' => 'pdo_mysql',
                        'charset' => 'UTF8',
                    ],
                ],
                'default_connection' => 'default',
            ], 'orm' => [
                'default_entity_manager' => 'default',
                'resolve_target_entities' => ['Symfony\Component\Security\Core\User\UserInterface' => 'stdClass'],
            ],
        ],
        ], $container);

        $container->setParameter('spiriit_form_filter.where_method', null);

        foreach ($configs as $extension => $config) {
            $container->loadFromExtension($extension, $config);
        }

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass(), new DecoratorServicePass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->addCompilerPass(new FormDataExtractorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        if ([] !== $publicServiceIds) {
            $container->addCompilerPass(new PublicServicesPass($publicServiceIds), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        }

        $container->compile(false);

        return $container;
    }
}
