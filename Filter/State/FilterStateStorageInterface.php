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
 * Keeps the state of a filter form between two requests.
 */
interface FilterStateStorageInterface
{
    public function save(FilterState $state): void;

    public function load(string $formName): ?FilterState;

    public function clear(string $formName): void;
}
