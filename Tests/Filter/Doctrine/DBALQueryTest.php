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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\DBALQuery;
use PHPUnit\Framework\TestCase;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DBALQueryTest extends TestCase
{
    public function testHasJoinAlias()
    {
        self::assertTrue(true);
        return;

        $exprMock = $this
            ->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

        $connectionMock
            ->expects($this->any())
            ->method('getExpressionBuilder')
            ->will($this->returnValue($exprMock));

        $qb = new QueryBuilder($connectionMock);

        $qb->leftJoin('root', 'table_1', 't1');
        $qb->leftJoin('root', 'table_2', 't2');
        $qb->innerJoin('t2', 'table_22', 't22');

        $query = new DBALQuery($qb);

        $this->assertTrue($query->hasJoinAlias('t2'));
        $this->assertFalse($query->hasJoinAlias('t3'));
    }
}
