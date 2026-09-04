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
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores filter states in the session, without ever starting one.
 *
 * Reading or writing a session starts it, which sets a cookie and makes the response private: a visitor
 * without an existing session keeps a cacheable, cookie-free response and simply gets no persistence.
 */
final class SessionFilterStateStorage implements FilterStateStorageInterface
{
    private const KEY_PREFIX = '_spiriit_form_filter.';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function save(FilterState $state): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            throw new SessionNotFoundException();
        }

        if (!$this->canUseSession($request)) {
            return;
        }

        $request->getSession()->set(self::KEY_PREFIX . $state->formName, $state->toArray());
    }

    public function load(string $formName): ?FilterState
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->canUseSession($request)) {
            return null;
        }

        $session = $request->getSession();
        $data = $session->get(self::KEY_PREFIX . $formName);

        if (!is_array($data)) {
            return null;
        }

        try {
            return FilterState::fromArray($data);
        } catch (InvalidArgumentException) {
            $session->remove(self::KEY_PREFIX . $formName);

            return null;
        }
    }

    public function clear(string $formName): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && $this->canUseSession($request)) {
            $request->getSession()->remove(self::KEY_PREFIX . $formName);
        }
    }

    /**
     * A session may be used when the visitor already sent one and no request of the stack is stateless.
     */
    private function canUseSession(Request $request): bool
    {
        if (!$request->hasSession() || !$request->hasPreviousSession()) {
            return false;
        }

        $mainRequest = $this->requestStack->getMainRequest();

        return !$request->attributes->getBoolean('_stateless')
            && (null === $mainRequest || !$mainRequest->attributes->getBoolean('_stateless'));
    }
}
