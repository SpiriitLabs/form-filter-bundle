<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Condition;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface ConditionNodeInterface
{
    public const EXPR_AND = 'and';
    public const EXPR_OR = 'or';

    /**
     * Start a OR sub expression.
     *
     * @return static
     */
    public function orX();

    /**
     * Start a AND sub expression.
     *
     * @return static
     */
    public function andX();

    /**
     * Returns the parent node.
     *
     * @return ConditionNode|null
     */
    public function end();

    /**
     * Add a field in the current expression.
     *
     * @param string $name
     * @return $this
     */
    public function field($name);

    /**
     * @return string
     */
    public function getOperator();

    /**
     * @return array
     */
    public function getFields();

    /**
     * @return array
     */
    public function getChildren();
}
