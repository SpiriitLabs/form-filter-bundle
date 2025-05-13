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
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Gilles Gauthier <g.gauthier@lexik.fr>
 */
class TextExtractionMethod implements DataExtractionMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    public function extract(FormInterface $form): array
    {
        $data = $form->getData();
        $values = ['value' => []];

        if (array_key_exists('text', $data)) {
            $values = ['value' => $data['text']];
            $values += $data;
        }

        return $values;
    }
}
