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

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\Condition;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\Expression\ExpressionBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\Expression\ORMExpressionBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class ORMQuery implements QueryInterface
{
    private QueryBuilder $queryBuilder;

    private ORMExpressionBuilder $expressionBuilder;

    /**
     * Constructor.
     *
     * @param boolean      $forceCaseInsensitivity
     * @param string|null  $encoding
     */
    public function __construct(QueryBuilder $queryBuilder, $forceCaseInsensitivity = false, $encoding = null)
    {
        $this->queryBuilder = $queryBuilder;
        $this->expressionBuilder = new ORMExpressionBuilder(
            $this->queryBuilder->expr(),
            $forceCaseInsensitivity,
            $encoding
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPartName(): string
    {
        return 'orm';
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
     */
    public function getExpr(): Expr
    {
        return $this->queryBuilder->expr();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootAlias()
    {
        $aliases = $this->queryBuilder->getRootAliases();

        return $aliases[0] ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function hasJoinAlias($joinAlias): bool
    {
        $joinParts = $this->queryBuilder->getDQLPart('join');

        /* @var \Doctrine\ORM\Query\Expr\Join $join */
        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $joinAlias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get expr class.
     *
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder(): ORMExpressionBuilder
    {
        return $this->expressionBuilder;
    }
}
