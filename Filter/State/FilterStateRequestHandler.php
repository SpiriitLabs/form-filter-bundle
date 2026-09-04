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

use LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Submits a filter form from the request, and falls back to its stored state when the request carries none.
 *
 * A valid state submitted from the request is stored; a restored state that turns out to be invalid is
 * forgotten, so a deleted entity or a vanished choice does not break every following request.
 */
final class FilterStateRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly RequestHandlerInterface $requestHandler,
        private readonly FilterStateStorageInterface $storage,
        private readonly string $resetParameter = '_reset',
    ) {
    }

    public function handleRequest(FormInterface $form, mixed $request = null): void
    {
        if (!$request instanceof Request) {
            throw new UnexpectedTypeException($request, Request::class);
        }

        $this->assertCsrfProtectionIsDisabled($form);

        $formName = $form->getName();

        if ($request->query->has($this->resetParameter)) {
            $this->storage->clear($formName);
        }

        $this->requestHandler->handleRequest($form, $request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->storage->save(FilterState::fromForm($form));
            }

            return;
        }

        $state = $this->storage->load($formName);

        if (null === $state) {
            return;
        }

        $state->applyTo($form);

        if (!$form->isValid()) {
            $this->storage->clear($formName);
        }
    }

    public function isFileUpload(mixed $data): bool
    {
        return $this->requestHandler->isFileUpload($data);
    }

    /**
     * A CSRF token cannot be replayed outside of the request that rendered it: permalinks and restored
     * states would make the form invalid on every request.
     */
    private function assertCsrfProtectionIsDisabled(FormInterface $form): void
    {
        $config = $form->getConfig();

        if ($config->hasOption('csrf_protection') && $config->getOption('csrf_protection')) {
            throw new LogicException(sprintf('Filter form "%s" cannot persist its state while CSRF protection is enabled, set the "csrf_protection" option to false (a filter is a read-only query).', $form->getName()));
        }
    }
}
