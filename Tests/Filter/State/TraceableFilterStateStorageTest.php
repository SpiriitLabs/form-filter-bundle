<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\State;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateOperation;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateOutcome;
use Spiriit\Bundle\FormFilterBundle\Filter\State\NullFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Filter\State\TraceableFilterStateStorage;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;

class TraceableFilterStateStorageTest extends TestCase
{
    public function testASavedStateIsRecordedWithItsValues(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());

        $storage->save($this->createState());

        $operations = $storage->getOperations();

        $this->assertCount(1, $operations);
        $this->assertSame('item_filter', $operations[0]->formName);
        $this->assertSame(FilterStateOutcome::Saved, $operations[0]->outcome);
        $this->assertSame(['name' => 'blabla'], $operations[0]->values);
    }

    /**
     * A storage that keeps nothing (no session yet, stateless request) must not be reported as having stored.
     */
    public function testAStateTheStorageDidNotKeepIsRecordedAsNotStored(): void
    {
        $storage = new TraceableFilterStateStorage(new NullFilterStateStorage());

        $storage->save($this->createState());

        $operations = $storage->getOperations();

        $this->assertSame(FilterStateOutcome::NotStored, $operations[0]->outcome);
        $this->assertSame(['name' => 'blabla'], $operations[0]->values);
    }

    public function testTheReadBackOfASaveIsNotRecordedAsALoad(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());

        $storage->save($this->createState());

        $this->assertCount(1, $storage->getOperations());
    }

    public function testARestoredStateIsRecordedAndReturned(): void
    {
        $inner = new InMemoryFilterStateStorage();
        $inner->save($this->createState());
        $storage = new TraceableFilterStateStorage($inner);

        $state = $storage->load('item_filter');

        $this->assertEquals($this->createState(), $state);

        $operations = $storage->getOperations();

        $this->assertCount(1, $operations);
        $this->assertSame(FilterStateOutcome::Restored, $operations[0]->outcome);
        $this->assertSame(['name' => 'blabla'], $operations[0]->values);
    }

    public function testAnEmptyStorageIsRecordedAsNothingStored(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());

        $this->assertNull($storage->load('item_filter'));

        $operations = $storage->getOperations();

        $this->assertSame('item_filter', $operations[0]->formName);
        $this->assertSame(FilterStateOutcome::NothingStored, $operations[0]->outcome);
        $this->assertNull($operations[0]->values);
    }

    public function testClearingIsRecordedAndDelegated(): void
    {
        $inner = new InMemoryFilterStateStorage();
        $inner->save($this->createState());
        $storage = new TraceableFilterStateStorage($inner);

        $storage->clear('item_filter');

        $this->assertNull($inner->load('item_filter'));
        $this->assertSame(1, $inner->clearCount());

        $operations = $storage->getOperations();

        $this->assertSame(FilterStateOutcome::Cleared, $operations[0]->outcome);
        $this->assertNull($operations[0]->values);
    }

    public function testTheOperationsAreKeptInOrder(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());

        $storage->clear('item_filter');
        $storage->save($this->createState());
        $storage->load('item_filter');

        $this->assertSame(
            [FilterStateOutcome::Cleared, FilterStateOutcome::Saved, FilterStateOutcome::Restored],
            array_map(static fn (FilterStateOperation $operation): FilterStateOutcome => $operation->outcome, $storage->getOperations())
        );
    }

    public function testResettingForgetsTheOperations(): void
    {
        $storage = new TraceableFilterStateStorage(new InMemoryFilterStateStorage());
        $storage->save($this->createState());

        $storage->reset();

        $this->assertSame([], $storage->getOperations());
    }

    public function testTheDecoratedStorageIsNamed(): void
    {
        $storage = new TraceableFilterStateStorage(new NullFilterStateStorage());

        $this->assertSame(NullFilterStateStorage::class, $storage->getStorageClass());
    }

    private function createState(): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => ['name' => 'blabla']]);
    }
}
