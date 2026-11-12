<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Condition;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface ConditionBuilderInterface
{
    /**
     * Create the root node.
     *
     * @param string $operator
     * @return ConditionNodeInterface
     */
    public function root($operator);

    /**
     * Add a condition to a node.
     */
    public function addCondition(ConditionInterface $condition);

    /**
     * Returns the root node.
     *
     * @return ConditionNodeInterface
     */
    public function getRoot();
}
