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

use RuntimeException;

/**
 * Used to build a condition nodes hierarchy to defined condition pattern.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ConditionBuilder implements ConditionBuilderInterface
{
    private ?ConditionNode $root = null;

    /**
     * {@inheritdoc}
     */
    public function root($operator)
    {
        $operator = strtolower($operator);

        if (!in_array($operator, [ConditionNodeInterface::EXPR_AND, ConditionNodeInterface::EXPR_OR])) {
            throw new RuntimeException(sprintf('Invalid operator "%s", allowed values: and, or', $operator));
        }

        $this->root = new ConditionNode($operator, null);

        return $this->root;
    }

    /**
     * {@inheritdoc}
     */
    public function addCondition(ConditionInterface $condition): void
    {
        if (false === $this->root->setCondition($condition->getName(), $condition)) {
            throw new RuntimeException(sprintf('Can\'t set condition object for: "%s"', $condition->getName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
        return $this->root;
    }
}
