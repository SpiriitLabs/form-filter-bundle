<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\Expression;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder as Expr;

class DBALExpressionBuilder extends ExpressionBuilder
{
    /**
     * Construct.
     */
    public function __construct(Expr $expr, $forceCaseInsensitivity, $encoding = null)
    {
        $this->expr = $expr;
        parent::__construct($forceCaseInsensitivity, $encoding);
    }
}
