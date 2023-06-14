<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\DataExtractor;

use Spiriit\Bundle\FormFilterBundle\Filter\DataExtractor\Method\DataExtractionMethodInterface;
use Symfony\Component\Form\FormInterface;

/**
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FormDataExtractor implements FormDataExtractorInterface
{
    /**
     * @var array
     */
    private $methods;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->methods = [];
    }

    /**
     * {@inheritdoc}
     */
    public function addMethod(DataExtractionMethodInterface $method)
    {
        $this->methods[$method->getName()] = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function extractData(FormInterface $form, $methodName)
    {
        if (!isset($this->methods[$methodName])) {
            throw new \RuntimeException(sprintf('Unknown extration method maned "%s".', $methodName));
        }

        return $this->methods[$methodName]->extract($form);
    }
}
