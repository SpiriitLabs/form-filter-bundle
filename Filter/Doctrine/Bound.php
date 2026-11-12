<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Doctrine;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Comparison;
use LogicException;

final class Bound
{
    private function __construct(
        private readonly int|float|string|DateTimeInterface $value,
        private readonly string $operator,
    ) {
    }

    public static function lower(mixed $value): self
    {
        return new self(self::orderable($value), Comparison::GTE);
    }

    public static function upper(mixed $value): self
    {
        return new self(self::orderable($value), Comparison::LTE);
    }

    public static function upperWholeDay(mixed $value): self
    {
        if (!$value instanceof DateTimeInterface) {
            throw new LogicException(\sprintf('A whole-day bound needs a date object, got "%s": set the "input" option of the DateType to "datetime" or "datetime_immutable".', get_debug_type($value)));
        }

        return new self(DateTimeImmutable::createFromInterface($value)->modify('+1 day'), Comparison::LT);
    }

    public function comparison(string $field, string $parameterName): Comparison
    {
        return new Comparison($field, $this->operator, ':' . $parameterName);
    }

    /**
     * @return array{0: int|float|string|DateTimeInterface, 1: string}
     */
    public function parameter(): array
    {
        return [$this->value, match (true) {
            \is_int($this->value) => Types::INTEGER,
            \is_float($this->value) => Types::FLOAT,
            $this->value instanceof DateTimeImmutable => Types::DATETIME_IMMUTABLE,
            $this->value instanceof DateTimeInterface => Types::DATETIME_MUTABLE,
            default => Types::STRING,
        }];
    }

    private static function orderable(mixed $value): int|float|string|DateTimeInterface
    {
        if (\is_int($value) || \is_float($value) || \is_string($value) || $value instanceof DateTimeInterface) {
            return $value;
        }

        throw new LogicException(\sprintf('A bound of type "%s" cannot be ordered in SQL, filter it with the "apply_filter" option instead.', get_debug_type($value)));
    }
}
