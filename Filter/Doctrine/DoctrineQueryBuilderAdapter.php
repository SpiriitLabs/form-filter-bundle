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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use RuntimeException;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineQueryBuilderAdapter
{
    public function __construct(private ORMQueryBuilder $qb)
    {
    }

    /**
     * @return Andx
     */
    public function andX()
    {
        return $this->qb->expr()->andX();
    }

    /**
     * @return Orx
     */
    public function orX()
    {
        return $this->qb->expr()->orX();
    }

    /**
     * @param mixed $where
     */
    public function where($where): void
    {
        $this->qb->where($where);
    }

    /**
     * @param mixed $where
     */
    public function andWhere($where): void
    {
        $this->qb->andWhere($where);
    }

    /**
     * @param mixed $where
     */
    public function orWhere($where): void
    {
        $this->qb->orWhere($where);
    }

    /**
     * @param string|int                                       $name
     * @param mixed                                            $value
     * @param ParameterType|ArrayParameterType|string|int|null $type
     */
    public function setParameter($name, $value, $type = null): void
    {
        $this->qb->setParameter($name, $value, $type);
    }
}
