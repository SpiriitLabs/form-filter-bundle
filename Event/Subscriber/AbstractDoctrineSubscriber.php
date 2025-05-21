<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Event\Subscriber;

use BackedEnum;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Spiriit\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Doctrine\ORMQuery;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use UnitEnum;

/**
 * Provide Doctrine ORM and DBAL filters.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
abstract class AbstractDoctrineSubscriber
{
    public function filterValue(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if ('' !== $values['value'] && null !== $values['value']) {
            $paramName = $this->generateParameterName($event->getField());

            if (is_array($values['value']) && sizeof($values['value']) > 0) {
                $event->setCondition(
                    $expr->in($event->getField(), ':' . $paramName),
                    [$paramName => [$values['value'], Connection::PARAM_STR_ARRAY]]
                );
            } elseif (!is_array($values['value'])) {
                $event->setCondition(
                    $expr->eq($event->getField(), ':' . $paramName),
                    [$paramName => [$values['value'], Types::STRING]]
                );
            }
        }
    }

    public function filterBoolean(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {
            $paramName = $this->generateParameterName($event->getField());

            $value = BooleanFilterType::VALUE_YES == $values['value'];

            $event->setCondition(
                $expr->eq($event->getField(), ':' . $paramName),
                [$paramName => [$value, Types::BOOLEAN]]
            );
        }
    }

    public function filterCheckbox(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {
            $paramName = $this->generateParameterName($event->getField());

            $event->setCondition(
                $expr->eq($event->getField(), ':' . $paramName),
                [$paramName => [$values['value'], Types::STRING]]
            );
        }
    }

    public function filterDate(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if ($values['value'] instanceof DateTime) {
            $paramName = $this->generateParameterName($event->getField());

            $event->setCondition(
                $expr->eq($event->getField(), ':' . $paramName),
                [$paramName => [$values['value'], Types::DATE_MUTABLE]]
            );
        }
    }

    public function filterDateRange(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpressionBuilder();
        $values = $event->getValues();
        $value = $values['value'];

        if (isset($value['left_date'][0]) || isset($value['right_date'][0])) {
            $event->setCondition($expr->dateInRange($event->getField(), $value['left_date'][0], $value['right_date'][0]));
        }
    }

    public function filterDateTime(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if ($values['value'] instanceof DateTime) {
            $paramName = $this->generateParameterName($event->getField());

            $event->setCondition(
                $expr->eq($event->getField(), ':' . $paramName),
                [$paramName => [$values['value'], Types::DATETIME_MUTABLE]]
            );
        }
    }

    public function filterDateTimeRange(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpressionBuilder();
        $values = $event->getValues();
        $value = $values['value'];

        if (isset($value['left_datetime'][0]) || isset($value['right_datetime'][0])) {
            $event->setCondition($expr->datetimeInRange($event->getField(), $value['left_datetime'][0], $value['right_datetime'][0]));
        }
    }

    public function filterNumber(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if ('' !== $values['value'] && null !== $values['value']) {
            $paramName = sprintf('p_%s', str_replace('.', '_', $event->getField()));

            $op = empty($values['condition_operator']) ? FilterOperands::OPERATOR_EQUAL : $values['condition_operator'];

            $event->setCondition(
                $expr->$op($event->getField(), ':' . $paramName),
                [$paramName => [$values['value'], is_int($values['value']) ? Types::INTEGER : Types::FLOAT]]
            );
        }
    }

    public function filterNumberRange(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();
        $value = $values['value'];

        $expression = $expr->andX();
        $params = [];

        if (isset($value['left_number'][0])) {
            $hasSelector = (FilterOperands::OPERAND_SELECTOR === $value['left_number']['condition_operator']);

            if (!$hasSelector && isset($value['left_number'][0])) {
                $leftValue = $value['left_number'][0];
                $leftCond = $value['left_number']['condition_operator'];
            } elseif ($hasSelector && isset($value['left_number'][0]['text'])) {
                $leftValue = $value['left_number'][0]['text'];
                $leftCond = $value['left_number'][0]['condition_operator'];
            }

            if (isset($leftValue, $leftCond)) {
                $leftParamName = sprintf('p_%s_left', str_replace('.', '_', $event->getField()));

                $expression->add($expr->$leftCond($event->getField(), ':' . $leftParamName));
                $params[$leftParamName] = [$leftValue, is_int($leftValue) ? Types::INTEGER : Types::FLOAT];
            }
        }

        if (isset($value['right_number'][0])) {
            $hasSelector = (FilterOperands::OPERAND_SELECTOR === $value['right_number']['condition_operator']);

            if (!$hasSelector && isset($value['right_number'][0])) {
                $rightValue = $value['right_number'][0];
                $rightCond = $value['right_number']['condition_operator'];
            } elseif ($hasSelector && isset($value['right_number'][0]['text'])) {
                $rightValue = $value['right_number'][0]['text'];
                $rightCond = $value['right_number'][0]['condition_operator'];
            }

            if (isset($rightValue, $rightCond)) {
                $rightParamName = sprintf('p_%s_right', str_replace('.', '_', $event->getField()));

                $expression->add($expr->$rightCond($event->getField(), ':' . $rightParamName));
                $params[$rightParamName] = [$rightValue, is_int($rightValue) ? Types::INTEGER : Types::FLOAT];
            }
        }

        if ($expression->count()) {
            $event->setCondition($expression, $params);
        }
    }

    public function filterText(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpressionBuilder();
        $values = $event->getValues();

        if ('' !== $values['value'] && null !== $values['value']) {
            if (isset($values['condition_pattern'])) {
                $event->setCondition($expr->stringLike($event->getField(), $values['value'], $values['condition_pattern']));
            } else {
                $event->setCondition($expr->stringLike($event->getField(), $values['value']));
            }
        }
    }

    public function filterEnum(GetFilterConditionEvent $event): void
    {
        /** @var ORMQuery $ormQuery */
        $ormQuery = $event->getFilterQuery();
        $expr = $ormQuery->getExpr();

        $values = $event->getValues();
        $value = $values['value'];

        if ('' !== $value && null !== $value && [] !== $value) {
            $paramName = $this->generateParameterName($event->getField());

            if (\is_array($value)) {
                $enumsValues = \array_map(static function (UnitEnum $enum): string {
                    if (!\is_a($enum, BackedEnum::class)) {
                        return $enum->name;
                    }

                    return $enum->value;
                }, $value);

                $event->setCondition(
                    $expr->in($event->getField(), ':' . $paramName),
                    [$paramName => [$enumsValues, ArrayParameterType::STRING]]
                );

                return;
            }

            $event->setCondition(
                (string) $expr->eq($event->getField(), \sprintf(':%s', $paramName)),
                [$paramName => [!\is_a($value, BackedEnum::class) ? $value->name : $value->value, Types::STRING]]
            );
        }
    }

    /**
     * @param string $field
     * @return string
     */
    protected function generateParameterName($field)
    {
        return sprintf('p_%s', str_replace('.', '_', $field));
    }
}
