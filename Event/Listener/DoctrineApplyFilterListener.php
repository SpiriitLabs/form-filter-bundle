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
use Doctrine\ORM\Query\Expr\Composite;
use Spiriit\Bundle\FormFilterBundle\Event\ApplyFilterConditionEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\DoctrineQueryBuilderAdapter;

/**
 * Add filter conditions on a Doctrine ORM or DBAL query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineApplyFilterListener
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $whereMethod;

    /**
     * @param string $whereMethod
     */
    public function __construct($whereMethod)
    {
        $this->whereMethod = empty($whereMethod) ? 'where' : sprintf('%sWhere', strtolower($whereMethod));
    }

    public function onApplyFilterCondition(ApplyFilterConditionEvent $event)
    {
        $qbAdapter = new DoctrineQueryBuilderAdapter($event->getQueryBuilder());
        $conditionBuilder = $event->getConditionBuilder();

        $this->parameters = [];
        $expression = $this->computeExpression($qbAdapter, $conditionBuilder->getRoot());

        if (null !== $expression && $expression->count()) {
            $qbAdapter->{$this->whereMethod}($expression);

            foreach ($this->parameters as $name => $value) {
                if (is_array($value)) {
                    [$value, $type] = $value;
                    $qbAdapter->setParameter($name, $value, $type);
                } else {
                    $qbAdapter->setParameter($name, $value);
                }
            }
        }
    }

    /**
     * @return Composite|CompositeExpression|null
     */
    protected function computeExpression(DoctrineQueryBuilderAdapter $queryBuilder, ConditionNodeInterface $node)
    {
        if (count($node->getFields()) == 0 && count($node->getChildren()) == 0) {
            return null;
        }

        $method = ($node->getOperator() == ConditionNodeInterface::EXPR_AND) ? 'andX' : 'orX';

        $expression = $queryBuilder->{$method}();

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
