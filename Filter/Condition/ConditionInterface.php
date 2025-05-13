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
interface ConditionInterface
{
    /**
     * Set the name to map the condition on the ConditionBuilder instance.
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Get condition path.
     *
     * @return string
     */
    public function getName();

    /**
     * Set the condition expression.
     *
     * @param string $expression
     */
    public function setExpression($expression);

    /**
     * Get the condition expression.
     *
     * @return string
     */
    public function getExpression();

    /**
     * Set expression parameters.
     */
    public function setParameters(array $parameters);

    /**
     * Get expression parameters.
     *
     * @return array
     */
    public function getParameters();
}
