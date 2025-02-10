<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FilterBuilderExecuterInterface
{
    /**
     * Add a join.
     *
     * @param string   $join
     * @param string   $alias
     * @param \Closure|null $callback
     */
    public function addOnce($join, $alias, ?\Closure $callback = null);

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @return RelationsAliasBag
     */
    public function getParts();

    /**
     * @return QueryInterface
     */
    public function getFilterQuery();
}
