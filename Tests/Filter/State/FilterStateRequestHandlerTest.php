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

use LogicException;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\FilterExtension;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterState;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateRequestHandler;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FilterStateRequestHandlerTest extends StateTestCase
{
    private InMemoryFilterStateStorage $storage;

    public function setUp(): void
    {
        parent::setUp();

        $this->storage = new InMemoryFilterStateStorage();
    }

    public function testAValidSubmissionIsStored(): void
    {
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[name]=blabla&item_filter[position]=2'));

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertSame(1, $this->storage->saveCount());
        $this->assertSame(['name' => 'blabla', 'position' => '2'], $this->storage->load('item_filter')->values);
    }

    public function testAnInvalidSubmissionIsNotStored(): void
    {
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[position]=abc'));

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame(0, $this->storage->saveCount());
        $this->assertNull($this->storage->load('item_filter'));
    }

    public function testAStoredStateIsRestoredWhenTheRequestCarriesNothing(): void
    {
        $this->storage->save($this->createState(['name' => 'blabla']));
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/'));

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertSame('blabla', $form->get('name')->getData());
    }

    public function testTheRequestWinsOverTheStoredState(): void
    {
        $this->storage->save($this->createState(['name' => 'stored']));
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[name]=submitted'));

        $this->assertSame('submitted', $form->get('name')->getData());
        $this->assertSame(['name' => 'submitted', 'position' => ''], $this->storage->load('item_filter')->values);
    }

    public function testAnEmptyStorageLeavesTheFormUnsubmitted(): void
    {
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/'));

        $this->assertFalse($form->isSubmitted());
    }

    public function testAnInvalidStoredStateIsForgotten(): void
    {
        $this->storage->save($this->createState(['position' => 'abc']));
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/'));

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame(1, $this->storage->clearCount());
        $this->assertNull($this->storage->load('item_filter'));
    }

    public function testTheResetParameterClearsTheStoredState(): void
    {
        $this->storage->save($this->createState(['name' => 'blabla']));
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/?_reset'));

        $this->assertSame(1, $this->storage->clearCount());
        $this->assertFalse($form->isSubmitted());
        $this->assertNull($this->storage->load('item_filter'));
    }

    public function testTheResetParameterClearsTheStoredStateBeforeANewSubmission(): void
    {
        $this->storage->save($this->createState(['name' => 'stored']));
        $form = $this->createFilterForm();

        $this->createHandler()->handleRequest($form, Request::create('/?_reset=&item_filter[name]=submitted'));

        $this->assertSame(1, $this->storage->clearCount());
        $this->assertSame('submitted', $form->get('name')->getData());
        $this->assertSame(['name' => 'submitted', 'position' => ''], $this->storage->load('item_filter')->values);
    }

    public function testTheResetParameterIsConfigurable(): void
    {
        $this->storage->save($this->createState(['name' => 'blabla']));
        $form = $this->createFilterForm();

        $this->createHandler('clear_filter')->handleRequest($form, Request::create('/?_reset'));

        $this->assertSame(0, $this->storage->clearCount());

        $this->createHandler('clear_filter')->handleRequest($this->createFilterForm(), Request::create('/?clear_filter'));

        $this->assertSame(1, $this->storage->clearCount());
    }

    public function testAPostFormIsRestoredFromTheStoredStateOnAGetRequest(): void
    {
        $this->storage->save($this->createState(['name' => 'blabla']));
        $form = $this->createFilterForm('POST');

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[name]=ignored'));

        $this->assertTrue($form->isSubmitted());
        $this->assertSame('blabla', $form->get('name')->getData());
    }

    public function testCsrfProtectionIsRejected(): void
    {
        if (!interface_exists(CsrfTokenManagerInterface::class)) {
            $this->markTestSkipped('The symfony/security-csrf component is not installed.');
        }

        $form = $this->createFilterForm('GET', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Filter form "item_filter" cannot persist its state while CSRF protection is enabled, set the "csrf_protection" option to false (a filter is a read-only query).');

        $this->createHandler()->handleRequest($form, Request::create('/'));
    }

    public function testDisabledCsrfProtectionIsAccepted(): void
    {
        if (!interface_exists(CsrfTokenManagerInterface::class)) {
            $this->markTestSkipped('The symfony/security-csrf component is not installed.');
        }

        $form = $this->createFilterForm('GET', false);

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[name]=blabla'));

        $this->assertSame(1, $this->storage->saveCount());
    }

    public function testAFormWithoutTheCsrfOptionIsAccepted(): void
    {
        $form = $this->createFilterForm();

        $this->assertFalse($form->getConfig()->hasOption('csrf_protection'));

        $this->createHandler()->handleRequest($form, Request::create('/?item_filter[name]=blabla'));

        $this->assertSame(1, $this->storage->saveCount());
    }

    public function testANonRequestIsRejected(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->createHandler()->handleRequest($this->createFilterForm());
    }

    public function testIsFileUploadIsDelegated(): void
    {
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())->method('isFileUpload')->with('blabla')->willReturn(true);

        $handler = new FilterStateRequestHandler($requestHandler, $this->storage);

        $this->assertTrue($handler->isFileUpload('blabla'));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createState(array $values): FilterState
    {
        return FilterState::fromArray(['form' => 'item_filter', 'values' => $values]);
    }

    private function createHandler(string $resetParameter = '_reset'): FilterStateRequestHandler
    {
        return new FilterStateRequestHandler(new HttpFoundationRequestHandler(), $this->storage, $resetParameter);
    }

    private function createFilterForm(string $method = 'GET', ?bool $csrfProtection = null): FormInterface
    {
        $options = ['method' => $method];

        if (null !== $csrfProtection) {
            $options['csrf_protection'] = $csrfProtection;
        }

        $builder = $this->createFormFactoryFor($csrfProtection)->createNamedBuilder('item_filter', FormType::class, null, $options);
        $builder->add('name', TextType::class, ['required' => false]);
        $builder->add('position', IntegerType::class, ['required' => false]);

        return $builder->getForm();
    }

    private function createFormFactoryFor(?bool $csrfProtection): FormFactory
    {
        if (null === $csrfProtection) {
            return $this->createFormFactory();
        }

        $resolvedFormTypeFactory = new ResolvedFormTypeFactory();
        $extensions = [
            new CoreExtension(),
            new HttpFoundationExtension(),
            new CsrfExtension($this->createMock(CsrfTokenManagerInterface::class)),
            new FilterExtension(),
        ];

        return new FormFactory(new FormRegistry($extensions, $resolvedFormTypeFactory), $resolvedFormTypeFactory);
    }
}
