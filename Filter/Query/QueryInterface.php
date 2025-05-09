<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Query;

use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
interface QueryInterface
{
    /**
     * Get query builder (of ORM, DBAL, ODM, Propel, etc.).
     *
     * @return mixed
     */
    public function getQueryBuilder();

    /**
     * Return a part name of filter events (ex: orm, dbal, propel, etc.).
     *
     * @return string
     */
    public function getEventPartName();

    /**
     * @param string $expression
     * @return ConditionInterface
     */
    public function createCondition($expression, array $parameters = []);

    /**
     * Get root alias.
     *
     * @return string
     */
    public function getRootAlias();

    /**
     * @param string $joinAlias
     * @return bool
     */
    public function hasJoinAlias($joinAlias);
}
