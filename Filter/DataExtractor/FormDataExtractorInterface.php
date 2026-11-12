<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
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
interface FormDataExtractorInterface
{
    /**
     * Add an extration method.
     */
    public function addMethod(DataExtractionMethodInterface $method);

    /**
     * Extract form data by using the given method.
     *
     * @param string        $methodName
     * @return array
     */
    public function extractData(FormInterface $form, $methodName);
}
