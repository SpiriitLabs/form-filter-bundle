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

use Spiriit\Bundle\FormFilterBundle\Filter\Explanation\FilterExplanation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class dispatched once a filter form has been applied to a query builder.
 */
final class FilterAppliedEvent extends Event
{
    public function __construct(
        private readonly object $queryBuilder,
        private readonly FilterExplanation $explanation,
    ) {
    }

    public function getQueryBuilder(): object
    {
        return $this->queryBuilder;
    }

    public function getExplanation(): FilterExplanation
    {
        return $this->explanation;
    }
}
