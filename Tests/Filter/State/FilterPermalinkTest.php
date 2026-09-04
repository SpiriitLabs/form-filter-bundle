<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\State;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateRequestHandler;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterUrlGenerator;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The whole loop: filter, share the URL, replay it on another visitor and get the same query.
 */
class FilterPermalinkTest extends StateTestCase
{
    private InMemoryFilterStateStorage $storage;

    public function setUp(): void
    {
        parent::setUp();

        $this->storage = new InMemoryFilterStateStorage();
    }

    public function testAPermalinkRebuildsTheSameQuery(): void
    {
        $submitted = $this->createFilterForm();
        $submitted->submit([
            'name' => ['text' => 'blabla', 'condition_pattern' => FilterOperands::STRING_ENDS],
            'position' => ['text' => 2, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL],
        ]);

        $url = $this->createUrlGenerator()->generate('item_list', FilterState::fromForm($submitted));

        $restored = $this->createFilterForm();
        $this->createHandler()->handleRequest($restored, Request::create($url));

        $this->assertTrue($restored->isSubmitted());
        $this->assertTrue($restored->isValid());
        $this->assertEquals($this->buildQuery($submitted), $this->buildQuery($restored));
    }

    public function testThePermalinkOfAnEmptyFilterUnfiltersTheListing(): void
    {
        $this->storage->save(FilterState::fromArray(['form' => 'item_filter', 'values' => ['name' => ['text' => 'blabla']]]));

        $emptied = $this->createFilterForm(false);
        $emptied->submit(['name' => '', 'position' => '']);

        $url = $this->createUrlGenerator()->generate('item_list', FilterState::fromForm($emptied));

        $restored = $this->createFilterForm(false);
        $this->createHandler()->handleRequest($restored, Request::create($url));

        $this->assertFalse($restored->isSubmitted());
        $this->assertNull($this->storage->load('item_filter'));
        $this->assertEquals($this->buildQuery($this->createFilterForm(false)), $this->buildQuery($restored));
    }

    private function createFilterForm(bool $withSelector = true): FormInterface
    {
        return $this->createFormFactory()->create(ItemFilterType::class, null, ['method' => 'GET', 'with_selector' => $withSelector]);
    }

    private function createHandler(): FilterStateRequestHandler
    {
        return new FilterStateRequestHandler(new HttpFoundationRequestHandler(), $this->storage);
    }

    private function createUrlGenerator(): FilterUrlGenerator
    {
        $routes = new RouteCollection();
        $routes->add('item_list', new Route('/items'));

        return new FilterUrlGenerator(new UrlGenerator($routes, new RequestContext()));
    }
}
