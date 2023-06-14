<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\DataExtractor\Method;

use Symfony\Component\Form\FormInterface;

/**
 * Extract data needed to apply a filter condition.
 *
 * @author <g.gauthier@lexik.com>
 * @author Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DefaultExtractionMethod implements DataExtractionMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function extract(FormInterface $form)
    {
        return ['value' => $form->getData()];
    }
}
