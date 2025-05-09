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

use Closure;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FilterBuilderExecuter implements FilterBuilderExecuterInterface
{
    protected QueryInterface $filterQuery;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected RelationsAliasBag $parts;

    /**
     * Construct.
     *
     * @param string            $alias
     */
    public function __construct(QueryInterface $filterQuery, $alias, RelationsAliasBag $parts)
    {
        $this->filterQuery = $filterQuery;
        $this->alias = $alias;
        $this->parts = $parts;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery()
    {
        return $this->filterQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function addOnce($join, $alias, Closure $callback = null)
    {
        if ($this->parts->has($join)) {
            return null;
        }

        $this->parts->add($join, $alias);

        if (!$callback instanceof Closure) {
            return;
        }

        return $callback($this->filterQuery->getQueryBuilder(), $this->alias, $alias, $this->filterQuery->getExpr());
    }
}
