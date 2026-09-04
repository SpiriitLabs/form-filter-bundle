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

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FilterUrlGeneratorTest extends TestCase
{
    public function testTheStateIsNestedUnderTheFormName(): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState(['name' => 'blabla', 'createdAt' => ['year' => '2024']]));

        $this->assertSame('/items', parse_url($url, PHP_URL_PATH));
        $this->assertSame(['item_filter' => ['name' => 'blabla', 'createdAt' => ['year' => '2024']]], $this->queryOf($url));
    }

    public function testEmptyValuesAreDropped(): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState([
            'name' => 'blabla',
            'position' => '',
            'colors' => [],
            'createdAt' => ['year' => '2024', 'month' => '', 'day' => ''],
            'updatedAt' => ['year' => ''],
        ]));

        $this->assertSame(['item_filter' => ['name' => 'blabla', 'createdAt' => ['year' => '2024']]], $this->queryOf($url));
    }

    /**
     * @dataProvider emptyStateProvider
     */
    public function testAnEmptyStateAsksForAReset(array $values): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState($values));

        $this->assertSame(['_reset' => ''], $this->queryOf($url));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function emptyStateProvider(): iterable
    {
        yield 'no value at all' => [[]];
        yield 'only empty strings' => [['name' => '', 'position' => '']];
        yield 'only empty arrays' => [['colors' => []]];
        yield 'only empty nested values' => [['createdAt' => ['year' => '', 'month' => '']]];
    }

    public function testExtraParametersAreMerged(): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState(['name' => 'blabla']), ['page' => 1]);

        $this->assertSame(['page' => '1', 'item_filter' => ['name' => 'blabla']], $this->queryOf($url));
    }

    public function testExtraParametersAreMergedWithAReset(): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState([]), ['page' => 1]);

        $this->assertSame(['page' => '1', '_reset' => ''], $this->queryOf($url));
    }

    public function testAnAbsoluteUrlCanBeGenerated(): void
    {
        $url = $this->createGenerator()->generate('item_list', $this->createState(['name' => 'blabla']), [], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->assertStringStartsWith('http://localhost/items?', $url);
        $this->assertSame(['item_filter' => ['name' => 'blabla']], $this->queryOf($url));
    }

    public function testTheResetParameterIsConfigurable(): void
    {
        $url = $this->createGenerator('clear_filter')->generate('item_list', $this->createState([]));

        $this->assertSame(['clear_filter' => ''], $this->queryOf($url));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createState(array $values): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => $values]);
    }

    private function createGenerator(string $resetParameter = '_reset'): FilterUrlGenerator
    {
        $routes = new RouteCollection();
        $routes->add('item_list', new Route('/items'));

        return new FilterUrlGenerator(new UrlGenerator($routes, new RequestContext()), $resetParameter);
    }

    /**
     * The generator percent-encodes the brackets of a nested parameter, so the query is compared once parsed.
     *
     * @return array<string, mixed>
     */
    private function queryOf(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }
}
