<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\State;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\Form\FormInterface;

/**
 * The submitted values of a filter form, in the shape a browser would send them.
 *
 * Only strings and nested arrays are held: a state is restored by re-submitting it to a form, so entities,
 * dates and enums are rebuilt by the form transformers instead of being serialized.
 */
final class FilterState
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        public readonly string $formName,
        public readonly array $values,
    ) {
    }

    public static function fromForm(FormInterface $form): self
    {
        if (!$form->isRoot()) {
            throw new InvalidArgumentException(sprintf('A filter state can only be captured from a root form, "%s" given.', $form->getName()));
        }

        if (!$form->getConfig()->getCompound()) {
            throw new InvalidArgumentException(sprintf('A filter state can only be captured from a compound form, "%s" is a single field.', $form->getName()));
        }

        return new self($form->getName(), self::normalizeValues(self::viewValues($form)));
    }

    /**
     * @param array{form?: mixed, values?: mixed} $data
     */
    public static function fromArray(array $data): self
    {
        $formName = $data['form'] ?? null;

        if (!is_string($formName) || '' === $formName) {
            throw new InvalidArgumentException(sprintf('A filter state requires a non empty "form" name, %s given.', get_debug_type($formName)));
        }

        $values = $data['values'] ?? null;

        if (!is_array($values)) {
            throw new InvalidArgumentException(sprintf('A filter state requires a "values" array, %s given.', get_debug_type($values)));
        }

        return new self($formName, self::normalizeValues($values));
    }

    /**
     * @return array{form: string, values: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['form' => $this->formName, 'values' => $this->values];
    }

    public function isFor(FormInterface $form): bool
    {
        return $form->getName() === $this->formName;
    }

    public function applyTo(FormInterface $form): void
    {
        if (!$this->isFor($form)) {
            throw new LogicException(sprintf('This filter state belongs to form "%s", it cannot be applied to "%s".', $this->formName, $form->getName()));
        }

        $form->submit($this->values);
    }

    /**
     * A compound view data is a model representation, never a browser payload: only leaves are read.
     */
    private static function viewValues(FormInterface $form): mixed
    {
        if (!$form->getConfig()->getCompound()) {
            return $form->getViewData();
        }

        $values = [];

        foreach ($form->all() as $name => $child) {
            $values[$name] = self::viewValues($child);
        }

        return $values;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function normalizeValues(array $values, string $path = ''): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $childPath = '' === $path ? (string) $key : sprintf('%s[%s]', $path, $key);
            $normalizedValue = self::normalizeValue($value, $childPath);

            if (null !== $normalizedValue) {
                $normalized[$key] = $normalizedValue;
            }
        }

        return $normalized;
    }

    private static function normalizeValue(mixed $value, string $path): mixed
    {
        if (is_array($value)) {
            return self::normalizeValues($value, $path);
        }

        // null and false are what an unchecked checkbox and an empty field send: nothing at all
        if (null === $value || false === $value) {
            return null;
        }

        if (true === $value) {
            return '1';
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('A filter state only holds strings and arrays, %s given at "%s".', get_debug_type($value), $path));
        }

        return (string) $value;
    }
}
