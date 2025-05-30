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
 * Represent a filter condition to ba added on a query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Condition implements ConditionInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    private $expression;

    /**
     * @var array
     *
     * array(
     *     'param_name_1' => $value,
     *     'param_nema_2  => array($value, $type),
     * )
     */
    private array $parameters;

    /**
     * @param string $expression
     */
    public function __construct($expression, array $parameters = [])
    {
        $this->expression = $expression;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpression($expression): void
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
