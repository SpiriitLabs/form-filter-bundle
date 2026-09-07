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

use Doctrine\ORM\EntityManager;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\FilterExtension;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\FilterStateTypeExtension;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Spiriit\Bundle\FormFilterBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\ResolvedFormTypeFactory;

/**
 * The shared factory of the persistence tests: TestCase::getFormFactory() has no HttpFoundation extension,
 * so handleRequest() is unusable with it.
 */
abstract class StateTestCase extends TestCase
{
    private ?EntityManager $em = null;

    /**
     * The state extension comes last, as the negative priority of its tag makes it in a real container.
     */
    protected function createFormFactory(?RequestHandlerInterface $requestHandler = null): FormFactory
    {
        $resolvedFormTypeFactory = new ResolvedFormTypeFactory();

        $extensions = [new CoreExtension(), new HttpFoundationExtension(), new FilterExtension()];

        if (null !== $requestHandler) {
            $extensions[] = new PreloadedExtension([], [FormType::class => [new FilterStateTypeExtension($requestHandler)]]);
        }

        return new FormFactory(new FormRegistry($extensions, $resolvedFormTypeFactory), $resolvedFormTypeFactory);
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    protected function buildQuery(FormInterface $form): array
    {
        $this->em ??= $this->getSqliteEntityManager();

        $queryBuilder = $this->em->getRepository(Item::class)->createQueryBuilder('i');
        $this->initQueryBuilderUpdater()->addFilterConditions($form, $queryBuilder);

        $parameters = [];

        foreach ($queryBuilder->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }

        return [$queryBuilder->getDQL(), $parameters];
    }
}
