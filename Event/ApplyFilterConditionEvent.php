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

use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class to compute the WHERE clause from the conditions.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ApplyFilterConditionEvent extends Event
{
    /**
     * @var mixed
     */
    private $queryBuilder;

    private ConditionBuilderInterface $conditionBuilder;

    /**
     * @param mixed                     $queryBuilder
     */
    public function __construct($queryBuilder, ConditionBuilderInterface $conditionBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->conditionBuilder = $conditionBuilder;
    }

    /**
     * @return mixed
     */
    public function getConditionBuilder()
    {
        return $this->conditionBuilder;
    }

    /**
     * @return mixed
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}
