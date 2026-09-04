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

/**
 * Forgets everything: use it to turn persistence off without touching the filter forms.
 */
final class NullFilterStateStorage implements FilterStateStorageInterface
{
    public function save(FilterState $state): void
    {
    }

    public function load(string $formName): ?FilterState
    {
        return null;
    }

    public function clear(string $formName): void
    {
    }
}
