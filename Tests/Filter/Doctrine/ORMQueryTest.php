<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\ORMQuery;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ORMQueryTest extends TestCase
{
    public function testHasJoinAlias(): void
    {
        $emMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $emMock
            ->expects($this->any())
            ->method('getExpressionBuilder')
            ->will($this->returnValue(new Expr()));

        $qb = new QueryBuilder($emMock);
        $qb->from('Root', 'r');
        $qb->leftJoin('r.association1', 'a1');
        $qb->leftJoin('r.association2', 'a2');
        $qb->innerJoin('a2.association22', 'a22');

        $query = new ORMQuery($qb);

        $this->assertTrue($query->hasJoinAlias('a2'));
        $this->assertFalse($query->hasJoinAlias('a3'));
    }
}
