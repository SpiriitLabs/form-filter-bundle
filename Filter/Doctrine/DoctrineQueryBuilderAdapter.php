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

use RuntimeException;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 * @deprecated use ORMQueryBuilder directly instead
 */
class DoctrineQueryBuilderAdapter
{
    private \Doctrine\ORM\QueryBuilder|DBALQueryBuilder $qb;

    /**
     * @param mixed $qb
     * @throws RuntimeException
     */
    public function __construct($qb)
    {
        trigger_deprecation('spiriitlabs/form-filter-bundle', '11.1.2', 'Using DoctrineQueryBuilderAdapter is deprecated, use ORMQueryBuilder directly instead.');

        if (!($qb instanceof ORMQueryBuilder || $qb  instanceof DBALQueryBuilder)) {
            throw new RuntimeException('Invalid Doctrine query builder instance.');
        }
        if ($qb instanceof DBALQueryBuilder) {
            trigger_deprecation('spiriitlabs/form-filter-bundle', '11.1.0', 'Using DBALQueryBuilder is deprecated, use ORMQueryBuilder instead.');
        }

        $this->qb = $qb;
    }

    /**
     * @return CompositeExpression|Andx
     */
    public function andX()
    {
        return $this->qb->expr()->andX();
    }

    /**
     * @return CompositeExpression|Orx
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
     * @param string      $name
     * @param mixed       $value
     * @param string|null $type
     */
    public function setParameter($name, $value, $type = null): void
    {
        $this->qb->setParameter($name, $value, $type);
    }
}
