<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\DataCollector;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use ReflectionClass;
use Spiriit\Bundle\FormFilterBundle\DataCollector\FilterDataCollector;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\NullFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Filter\State\TraceableFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Tests\TestCase;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class FilterTemplateTest extends TestCase
{
    private const TEMPLATE = '@SpiriitFormFilter/Collector/filter.html.twig';

    private EntityManager $em;

    private FilterDataCollector $collector;

    private Environment $twig;

    public function setUp(): void
    {
        if (!class_exists(Environment::class) || !class_exists(WebProfilerExtension::class)) {
            $this->markTestSkipped('twig/twig and symfony/web-profiler-bundle are required to render the profiler panel.');
        }

        parent::setUp();

        $container = $this->initContainer(true, ['spiriit_form_filter.data_collector']);
        $updater = $container->get('spiriit_form_filter.query_builder_updater');
        $this->collector = $container->get('spiriit_form_filter.data_collector');
        $this->em = $this->getSqliteEntityManager();

        $form = $this->formFactory->create(ItemFilterType::class);
        $form->submit(['name' => 'blabla']);
        $updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $form = $this->formFactory->createNamedBuilder('item_filter', SymfonyFormType::class)
            ->add('comment', TextareaType::class)
            ->add('name', TextFilterType::class)
            ->getForm()
        ;
        $form->submit(['comment' => 'hello', 'name' => '']);
        $updater->addFilterConditions($form, $this->createDoctrineQueryBuilder());

        $this->collector->collect(new Request(), new Response());

        $this->twig = $this->createTwig();
    }

    public function testToolbarBlock(): void
    {
        $rendered = $this->renderBlock('toolbar', $this->toolbarContext($this->collector));

        $this->assertStringContainsString('sf-toolbar-block-spiriit_form_filter', $rendered);
        $this->assertStringContainsString('sf-toolbar-status-yellow', $rendered);
        $this->assertStringContainsString('Applied conditions', $rendered);
        $this->assertStringContainsString('Fields without listener', $rendered);
        $this->assertStringContainsString('<span class="sf-toolbar-value">1</span>', $rendered);
    }

    public function testToolbarBlockIsEmptyWithoutRuns(): void
    {
        $collector = new FilterDataCollector();
        $collector->collect(new Request(), new Response());

        $this->assertSame('', trim($this->renderBlock('toolbar', $this->toolbarContext($collector))));
    }

    public function testMenuBlock(): void
    {
        $rendered = $this->renderBlock('menu', $this->panelContext($this->collector));

        $this->assertStringContainsString('Form filter', $rendered);
        $this->assertStringContainsString('label-status-warning', $rendered);
        $this->assertStringContainsString('<svg', $rendered);
    }

    public function testPanelBlock(): void
    {
        $rendered = $this->renderBlock('panel', $this->panelContext($this->collector));

        $this->assertStringContainsString('<code>name</code>', $rendered);
        $this->assertStringContainsString('spiriit_form_filter.apply.orm.filter_text', $rendered);
        $this->assertStringContainsString('status-success', $rendered);
        $this->assertStringContainsString('no listener', $rendered);
        $this->assertStringContainsString('status-warning', $rendered);
        $this->assertStringContainsString('i.name LIKE', $rendered);
        $this->assertStringContainsString('SELECT i FROM', $rendered);
        $this->assertStringContainsString('blabla', $rendered);
        $this->assertStringContainsString('AND', $rendered);
        $this->assertStringContainsString('sf-dump', $rendered);
    }

    public function testPanelKeepsTheFieldsTableReadable(): void
    {
        $rendered = $this->renderBlock('panel', $this->panelContext($this->collector));

        // the dumps live in their own full-width row, so the table itself stays narrow
        $this->assertStringContainsString('class="filter-fields"', $rendered);
        $this->assertStringContainsString('<td colspan="5">', $rendered);
        $this->assertStringContainsString('<details>', $rendered);

        // the form type is shortened, its FQCN stays available as a tooltip
        $this->assertStringContainsString('>TextFilterType<', $rendered);
        $this->assertStringContainsString('title="' . TextFilterType::class . '"', $rendered);
        $this->assertStringNotContainsString('>' . TextFilterType::class . '<', $rendered);
    }

    public function testPanelBlockListsThePersistedStates(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());
        $collector = new FilterDataCollector($storage, 'clear_filter');

        $storage->save($this->createState());
        $storage->load('item_filter');
        $storage->clear('item_filter');

        $collector->collect(new Request(), new Response());

        $rendered = $this->renderBlock('panel', $this->panelContext($collector));

        $this->assertStringContainsString('Persistence', $rendered);
        $this->assertStringContainsString(InMemoryFilterStateStorage::class, $rendered);
        $this->assertStringContainsString('<code>clear_filter</code>', $rendered);
        $this->assertStringContainsString('>saved</span>', $rendered);
        $this->assertStringContainsString('>restored</span>', $rendered);
        $this->assertStringContainsString('>cleared</span>', $rendered);
        $this->assertStringContainsString('blabla', $rendered);
        $this->assertStringContainsString('sf-dump', $rendered);

        // the panel is worth showing even when nothing was applied to a query builder
        $this->assertStringNotContainsString('empty-panel', $rendered);
        $this->assertStringContainsString('No filter form was applied', $rendered);
    }

    public function testPanelBlockWarnsAboutAStateTheStorageKeptNothingOf(): void
    {
        $storage = new TraceableFilterStateStorage(new NullFilterStateStorage());
        $collector = new FilterDataCollector($storage, '_reset');

        $storage->save($this->createState());

        $collector->collect(new Request(), new Response());

        $rendered = $this->renderBlock('panel', $this->panelContext($collector));

        $this->assertStringContainsString('>not stored</span>', $rendered);
        $this->assertStringContainsString('status-warning', $rendered);
        $this->assertStringContainsString('never starts a session', $rendered);

        $toolbar = $this->renderBlock('toolbar', $this->toolbarContext($collector));

        $this->assertStringContainsString('sf-toolbar-status-yellow', $toolbar);
        $this->assertStringContainsString('States saved', $toolbar);

        $menu = $this->renderBlock('menu', $this->panelContext($collector));

        $this->assertStringContainsString('label-status-warning', $menu);
        $this->assertStringNotContainsString('disabled', $menu);
    }

    public function testPanelBlockTellsHowToEnablePersistence(): void
    {
        $rendered = $this->renderBlock('panel', $this->panelContext($this->collector));

        $this->assertStringContainsString('No filter form persisted its state', $rendered);
        $this->assertStringContainsString('<code>filter_persistence</code>', $rendered);
    }

    public function testPanelBlockWithoutAnyRun(): void
    {
        $collector = new FilterDataCollector();
        $collector->collect(new Request(), new Response());

        $rendered = $this->renderBlock('panel', $this->panelContext($collector));

        $this->assertStringContainsString('empty-panel', $rendered);
        $this->assertStringContainsString('No filter form was applied', $rendered);
    }

    private function createState(): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => ['name' => 'blabla']]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderBlock(string $block, array $context): string
    {
        return $this->twig->load(self::TEMPLATE)->renderBlock($block, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function toolbarContext(FilterDataCollector $collector): array
    {
        return [
            'collector' => $collector,
            'name' => FilterDataCollector::NAME,
            'token' => 'abc123',
            'profiler_url' => '#profiler',
            'profiler_markup_version' => 3,
            'csp_script_nonce' => null,
            'csp_style_nonce' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panelContext(FilterDataCollector $collector): array
    {
        return [
            'collector' => $collector,
            'token' => 'abc123',
            'panel' => FilterDataCollector::NAME,
            'page' => 'home',
            'request' => new Request(),
            'templates' => [],
            'is_ajax' => false,
            'profiler_markup_version' => 3,
            'profile_type' => 'request',
        ];
    }

    private function createTwig(): Environment
    {
        $webProfilerBundle = new ReflectionClass(WebProfilerBundle::class);

        $loader = new FilesystemLoader();
        $loader->addPath(dirname($webProfilerBundle->getFileName()) . '/Resources/views', 'WebProfiler');
        $loader->addPath(dirname(__DIR__, 2) . '/Resources/views', 'SpiriitFormFilter');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'cache' => false,
            'debug' => true,
        ]);

        $twig->addExtension(new WebProfilerExtension(new HtmlDumper()));

        // toolbar_item.html.twig links to the profiler with url(), which needs the routing extension
        $twig->addFunction(new TwigFunction('url', static fn (string $route, array $parameters = []): string => '#' . $route));

        return $twig;
    }

    private function createDoctrineQueryBuilder(string $entityClassName = Item::class, string $alias = 'i'): QueryBuilder
    {
        return $this->em
                    ->getRepository($entityClassName)
                    ->createQueryBuilder($alias)
        ;
    }
}
