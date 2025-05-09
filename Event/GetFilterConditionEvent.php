<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Event;

use Spiriit\Bundle\FormFilterBundle\Filter\Condition\Condition;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class GetFilterConditionEvent extends Event
{
    /**
     * @var QueryInterface $filterQuery
     */
    private $filterQuery;

    /**
     * @var string $field
     */
    private $field;

    /**
     * @var array $values
     */
    private $values;

    /**
     * @var ConditionInterface
     */
    private $condition;

    /**
     * Construct.
     *
     * @param string         $field
     * @param array          $values
     */
    public function __construct(QueryInterface $filterQuery, $field, $values)
    {
        $this->filterQuery = $filterQuery;
        $this->field = $field;
        $this->values = $values;
    }

    /**
     * @return QueryInterface
     */
    public function getFilterQuery()
    {
        return $this->filterQuery;
    }

    /**
     * @return object
     */
    public function getQueryBuilder()
    {
        return $this->filterQuery->getQueryBuilder();
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param string $expression
     */
    public function setCondition($expression, array $parameters = [])
    {
        $this->condition = new Condition($expression, $parameters);
    }

    /**
     * @return ConditionInterface
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
