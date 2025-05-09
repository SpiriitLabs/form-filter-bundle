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

use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Event\PrepareEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\DBALQuery;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\ORMQuery;

class PrepareListener
{
    protected bool $forceCaseInsensitivity = false;
    protected ?string $encoding = null;

    public function getForceCaseInsensitivity(): bool
    {
        return $this->forceCaseInsensitivity;
    }

    public function setForceCaseInsensitivity(bool $forceCaseInsensitivity): void
    {
        $this->forceCaseInsensitivity = $forceCaseInsensitivity;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setEncoding(?string $encoding): void
    {
        $this->encoding = $encoding;
    }

    /**
     * Filter builder prepare event
     */
    public function onFilterBuilderPrepare(PrepareEvent $event): void
    {
        $qb = $event->getQueryBuilder();

        $queryClasses = [QueryBuilder::class => ORMQuery::class, \Doctrine\DBAL\Query\QueryBuilder::class => DBALQuery::class];

        foreach ($queryClasses as $builderClass => $queryClass) {
            if (class_exists($builderClass) && $qb instanceof $builderClass) {
                $query = new $queryClass($qb, $this->getForceCaseInsensitivity(), $this->encoding);

                $event->setFilterQuery($query);
                $event->stopPropagation();

                return;
            }
        }
    }
}
