<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\RelationsAliasBag;

class RelationsAliasBagTest extends TestCase
{
    public function testAllReturnsEveryRegisteredAlias(): void
    {
        $bag = new RelationsAliasBag(['i.options' => 'opt']);
        $bag->add('__root__', 'i');

        $this->assertSame(['i.options' => 'opt', '__root__' => 'i'], $bag->all());
    }

    public function testAllIsASnapshot(): void
    {
        $bag = new RelationsAliasBag(['i.options' => 'opt']);

        $aliases = $bag->all();
        $bag->add('i.tags', 'tag');

        $this->assertSame(['i.options' => 'opt'], $aliases);
    }
}
