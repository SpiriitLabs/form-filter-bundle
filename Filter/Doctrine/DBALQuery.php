<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Doctrine;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\Condition;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\Expression\DBALExpressionBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class DBALQuery implements QueryInterface
{
    private QueryBuilder $queryBuilder;

    private DBALExpressionBuilder $expressionBuilder;

    /**
     * Constructor.
     *
     * @param boolean      $forceCaseInsensitivity
     */
    public function __construct(QueryBuilder $queryBuilder, $forceCaseInsensitivity = false)
    {
        $this->queryBuilder = $queryBuilder;
        $this->expressionBuilder = new DBALExpressionBuilder(
            $this->queryBuilder->expr(),
            $forceCaseInsensitivity
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPartName(): string
    {
        return 'dbal';
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function createCondition($expression, array $parameters = []): Condition
    {
        return new Condition($expression, $parameters);
    }

    /**
     * Get QueryBuilder expr.
     *
     * @return ExpressionBuilder
     */
    public function getExpr()
    {
        return $this->queryBuilder->expr();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootAlias()
    {
        $from = $this->queryBuilder->getQueryPart('from');

        return $from[0]['alias'];
    }

    /**
     * {@inheritDoc}
     */
    public function hasJoinAlias($joinAlias): bool
    {
        $joinParts = $this->queryBuilder->getQueryPart('join');

        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ($join['joinAlias'] === $joinAlias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get expr class.
     *
     * @return \Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\Expression\ExpressionBuilder
     */
    public function getExpressionBuilder(): DBALExpressionBuilder
    {
        return $this->expressionBuilder;
    }
}
