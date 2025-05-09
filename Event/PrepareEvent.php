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

use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Get alias and expression builder for filter builder
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class PrepareEvent extends Event
{
    /**
     * @var object $queryBuilder
     */
    private $queryBuilder;

    private ?QueryInterface $filterQuery = null;

    /**
     * Construct
     *
     * @param object $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Get query builder
     *
     * @return object
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Set filter query
     */
    public function setFilterQuery(QueryInterface $filterQuery): void
    {
        $this->filterQuery = $filterQuery;
    }

    /**
     * Get filter query
     *
     * @return QueryInterface
     */
    public function getFilterQuery()
    {
        return $this->filterQuery;
    }
}
