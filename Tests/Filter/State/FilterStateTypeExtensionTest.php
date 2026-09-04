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

use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateRequestHandler;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Stubs\InMemoryFilterStateStorage;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FilterStateTypeExtensionTest extends StateTestCase
{
    private FilterStateRequestHandler $requestHandler;

    private FormFactory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestHandler = new FilterStateRequestHandler(new HttpFoundationRequestHandler(), new InMemoryFilterStateStorage());
        $this->factory = $this->createFormFactory($this->requestHandler);
    }

    public function testTheDefaultRequestHandlerIsLeftInPlace(): void
    {
        $form = $this->factory->create(ItemFilterType::class);

        $this->assertFalse($form->getConfig()->getOption('filter_persistence'));
        $this->assertInstanceOf(HttpFoundationRequestHandler::class, $form->getConfig()->getRequestHandler());
    }

    public function testAPersistedFormGetsTheStateRequestHandler(): void
    {
        $form = $this->factory->create(ItemFilterType::class, null, ['filter_persistence' => true]);

        $this->assertSame($this->requestHandler, $form->getConfig()->getRequestHandler());
    }

    public function testTheOptionOnlyAcceptsBooleans(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(ItemFilterType::class, null, ['filter_persistence' => 'yes']);
    }
}
