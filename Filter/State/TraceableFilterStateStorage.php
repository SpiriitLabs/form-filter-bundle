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
 * Records what the decorated storage was asked to keep, for the web profiler.
 */
final class TraceableFilterStateStorage implements FilterStateStorageInterface
{
    /**
     * @var list<FilterStateOperation>
     */
    private array $operations = [];

    public function __construct(
        private readonly FilterStateStorageInterface $storage,
    ) {
    }

    public function save(FilterState $state): void
    {
        $this->storage->save($state);

        // a storage may silently keep nothing (no session yet, stateless request): read it back rather than guess
        $this->operations[] = FilterStateOperation::saved($state, null !== $this->storage->load($state->formName));
    }

    public function load(string $formName): ?FilterState
    {
        $state = $this->storage->load($formName);

        $this->operations[] = FilterStateOperation::loaded($formName, $state);

        return $state;
    }

    public function clear(string $formName): void
    {
        $this->storage->clear($formName);

        $this->operations[] = FilterStateOperation::cleared($formName);
    }

    /**
     * @return list<FilterStateOperation>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getStorageClass(): string
    {
        return get_debug_type($this->storage);
    }

    public function reset(): void
    {
        $this->operations = [];
    }
}
