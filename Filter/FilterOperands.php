<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter;

use ReflectionClass;

/**
 * This class aim to regroup constants used in form filter types and in expression classes.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
final class FilterOperands
{
    public const OPERATOR_EQUAL = 'eq';
    public const OPERATOR_GREATER_THAN = 'gt';
    public const OPERATOR_GREATER_THAN_EQUAL = 'gte';
    public const OPERATOR_LOWER_THAN = 'lt';
    public const OPERATOR_LOWER_THAN_EQUAL = 'lte';

    public const STRING_STARTS = 1;
    public const STRING_ENDS = 2;
    public const STRING_EQUALS = 3;
    public const STRING_CONTAINS = 4;

    public const OPERAND_SELECTOR = 'selection';

    /**
     * Returns all available number operands.
     *
     * @param boolean $includeSelector
     */
    public static function getNumberOperands($includeSelector = false): array
    {
        $values = [self::OPERATOR_EQUAL, self::OPERATOR_GREATER_THAN, self::OPERATOR_GREATER_THAN_EQUAL, self::OPERATOR_LOWER_THAN, self::OPERATOR_LOWER_THAN_EQUAL];

        if ($includeSelector) {
            $values[] = self::OPERAND_SELECTOR;
        }

        return $values;
    }

    /**
     * Returns all available string operands.
     *
     * @param boolean $includeSelector
     */
    public static function getStringOperands($includeSelector = false): array
    {
        $values = [self::STRING_STARTS, self::STRING_ENDS, self::STRING_EQUALS, self::STRING_CONTAINS];

        if ($includeSelector) {
            $values[] = self::OPERAND_SELECTOR;
        }

        return $values;
    }

    /**
     * Retruns an array of available conditions operator for numbers.
     */
    public static function getNumberOperandsChoices(): array
    {
        $choices = [];

        $reflection = new ReflectionClass(self::class);
        foreach ($reflection->getConstants() as $name => $value) {
            if ('OPERATOR_' === substr($name, 0, 9)) {
                $choices[$value] = strtolower(str_replace('OPERATOR_', 'number.', $name));
            }
        }

        return array_flip($choices);
    }

    /**
     * Retruns an array of available conditions patterns for string.
     */
    public static function getStringOperandsChoices(): array
    {
        $choices = [];

        $reflection = new ReflectionClass(self::class);
        foreach ($reflection->getConstants() as $name => $value) {
            if ('STRING_' === substr($name, 0, 7)) {
                $choices[$value] = strtolower(str_replace('STRING_', 'text.', $name));
            }
        }

        return array_flip($choices);
    }

    /**
     * Returns class constant string operand by given string.
     *
     * @param String $operand
     * @return int
     */
    public static function getStringOperandByString($operand)
    {
        if ($operand === null) {
            return self::STRING_STARTS;
        }

        $name = strtoupper(str_replace('text.', 'STRING_', $operand));
        $reflection = new ReflectionClass(self::class);

        return $reflection->getConstant($name);
    }
}
