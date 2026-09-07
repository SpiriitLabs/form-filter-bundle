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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds a shareable URL carrying the state of a filter form in the query string.
 */
final class FilterUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $resetParameter = '_reset',
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $route, FilterState $state, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $values = self::withoutEmptyValues($state->values);

        // an empty filter vanishes from the query string, and the stored state would be restored instead
        if ([] === $values) {
            $parameters[$this->resetParameter] = '';
        } else {
            $parameters[$state->formName] = $values;
        }

        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function withoutEmptyValues(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = self::withoutEmptyValues($value);
            }

            if ('' === $value || [] === $value) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }
}
