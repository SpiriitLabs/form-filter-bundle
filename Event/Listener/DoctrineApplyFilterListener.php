<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Event\Listener;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Event\ApplyFilterConditionEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;

/**
 * Add filter conditions on a Doctrine ORM or DBAL query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
final class DoctrineApplyFilterListener
{
    private ?array $parameters = null;

    private string $whereMethod;

    /**
     * @param string $whereMethod
     */
    public function __construct($whereMethod)
    {
        $this->whereMethod = empty($whereMethod) ? 'where' : sprintf('%sWhere', strtolower($whereMethod));
    }

    public function onApplyFilterCondition(ApplyFilterConditionEvent $event): void
    {
        /** @var QueryBuilder $qb */
        $qb = $event->getQueryBuilder();
        $conditionBuilder = $event->getConditionBuilder();

        $this->parameters = [];
        $expression = $this->computeExpression($qb, $conditionBuilder->getRoot());

        if (null !== $expression && $expression->count()) {
            $qb->{$this->whereMethod}($expression);

            foreach ($this->parameters as $name => $value) {
                if (is_array($value)) {
                    [$value, $type] = $value;
                    $qb->setParameter($name, $value, $type);
                } else {
                    $qb->setParameter($name, $value);
                }
            }
        }
    }

    /**
     * @return Composite|CompositeExpression|null
     */
    private function computeExpression(QueryBuilder $queryBuilder, ConditionNodeInterface $node)
    {
        if (count($node->getFields()) == 0 && count($node->getChildren()) == 0) {
            return null;
        }

        $method = ($node->getOperator() == ConditionNodeInterface::EXPR_AND) ? 'andX' : 'orX';

        $expression = $queryBuilder->expr()->{$method}();

        foreach ($node->getFields() as $condition) {
            if (null !== $condition) {
                /** @var ConditionInterface $condition */
                $expression->add($condition->getExpression());

                $this->parameters = array_merge($this->parameters, $condition->getParameters());
            }
        }

        foreach ($node->getChildren() as $child) {
            $subExpr = $this->computeExpression($queryBuilder, $child);

            if (null !== $subExpr && $subExpr->count()) {
                $expression->add($subExpr);
            }
        }

        return $expression->count() ? $expression : null;
    }
}
