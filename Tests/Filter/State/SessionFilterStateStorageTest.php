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
use Spiriit\Bundle\FormFilterBundle\Filter\State\SessionFilterStateStorage;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class SessionFilterStateStorageTest extends TestCase
{
    private const SESSION_KEY = '_spiriit_form_filter.item_filter';

    public function testAStateIsStoredAndRestored(): void
    {
        $session = $this->createSession();
        $storage = $this->createStorage($this->createRequest($session, true));
        $state = $this->createState();

        $storage->save($state);

        $this->assertSame($state->toArray(), $session->get(self::SESSION_KEY));
        $this->assertEquals($state, $storage->load('item_filter'));

        $storage->clear('item_filter');

        $this->assertFalse($session->has(self::SESSION_KEY));
        $this->assertNull($storage->load('item_filter'));
    }

    public function testAnotherFormIsNotRestored(): void
    {
        $storage = $this->createStorage($this->createRequest($this->createSession(), true));
        $storage->save($this->createState());

        $this->assertNull($storage->load('other_filter'));
    }

    public function testNothingHappensWithoutAPreviousSession(): void
    {
        $session = $this->createSession();
        $storage = $this->createStorage($this->createRequest($session, false));

        $storage->save($this->createState());
        $storage->clear('item_filter');

        $this->assertNull($storage->load('item_filter'));
        $this->assertFalse($session->isStarted());
        $this->assertSame(0, $session->getUsageIndex());
    }

    public function testNothingHappensOnAStatelessRequest(): void
    {
        $session = $this->createSession();
        $request = $this->createRequest($session, true);
        $request->attributes->set('_stateless', true);

        $storage = $this->createStorage($request);

        $storage->save($this->createState());
        $storage->clear('item_filter');

        $this->assertNull($storage->load('item_filter'));
        $this->assertFalse($session->isStarted());
        $this->assertSame(0, $session->getUsageIndex());
    }

    public function testNothingHappensWhenOnlyTheMainRequestIsStateless(): void
    {
        $session = $this->createSession();

        $mainRequest = $this->createRequest($session, true);
        $mainRequest->attributes->set('_stateless', true);

        $requestStack = new RequestStack();
        $requestStack->push($mainRequest);
        $requestStack->push($this->createRequest($session, true));

        $storage = new SessionFilterStateStorage($requestStack);

        $storage->save($this->createState());
        $storage->clear('item_filter');

        $this->assertNull($storage->load('item_filter'));
        $this->assertFalse($session->isStarted());
        $this->assertSame(0, $session->getUsageIndex());
    }

    public function testSavingWithoutAnyRequestFails(): void
    {
        $storage = new SessionFilterStateStorage(new RequestStack());

        $this->expectException(SessionNotFoundException::class);

        $storage->save($this->createState());
    }

    public function testSavingWithoutASessionFails(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->createStorage(Request::create('/'))->save($this->createState());
    }

    public function testLoadingAndClearingWithoutAnyRequestIsSilent(): void
    {
        $storage = new SessionFilterStateStorage(new RequestStack());

        $storage->clear('item_filter');

        $this->assertNull($storage->load('item_filter'));
    }

    /**
     * @dataProvider corruptedDataProvider
     */
    public function testCorruptedDataIsForgotten(mixed $data): void
    {
        $session = $this->createSession();
        $session->set(self::SESSION_KEY, $data);

        $storage = $this->createStorage($this->createRequest($session, true));

        $this->assertNull($storage->load('item_filter'));
        $this->assertFalse($session->has(self::SESSION_KEY));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function corruptedDataProvider(): iterable
    {
        yield 'an object among the values' => [['form' => 'item_filter', 'values' => [new stdClass()]]];
        yield 'no values' => [['form' => 'item_filter']];
        yield 'no form name' => [['values' => ['name' => 'blabla']]];
    }

    public function testNonArrayDataIsIgnored(): void
    {
        $session = $this->createSession();
        $session->set(self::SESSION_KEY, 'blabla');

        $storage = $this->createStorage($this->createRequest($session, true));

        $this->assertNull($storage->load('item_filter'));
        $this->assertTrue($session->has(self::SESSION_KEY));
    }

    private function createState(): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => ['name' => 'blabla']]);
    }

    private function createStorage(Request $request): SessionFilterStateStorage
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new SessionFilterStateStorage($requestStack);
    }

    private function createSession(): SessionInterface
    {
        return new Session(new MockArraySessionStorage());
    }

    private function createRequest(SessionInterface $session, bool $withSessionCookie): Request
    {
        $request = Request::create('/');
        $request->setSession($session);

        if ($withSessionCookie) {
            $request->cookies->set($session->getName(), 'a-session-id');
        }

        return $request;
    }
}
