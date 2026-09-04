<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Stubs;

use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface;

/**
 * Records what the request handler asked for, without a session.
 */
final class InMemoryFilterStateStorage implements FilterStateStorageInterface
{
    /**
     * @var array<string, FilterState>
     */
    private array $states = [];

    private int $saveCount = 0;

    private int $clearCount = 0;

    public function save(FilterState $state): void
    {
        ++$this->saveCount;
        $this->states[$state->formName] = $state;
    }

    public function load(string $formName): ?FilterState
    {
        return $this->states[$formName] ?? null;
    }

    public function clear(string $formName): void
    {
        ++$this->clearCount;
        unset($this->states[$formName]);
    }

    public function saveCount(): int
    {
        return $this->saveCount;
    }

    public function clearCount(): int
    {
        return $this->clearCount;
    }
}
