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
class ValueKeysExtractionMethod implements DataExtractionMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'value_keys';
    }

    /**
     * {@inheritdoc}
     * @return non-empty-array[][]
     */
    public function extract(FormInterface $form): array
    {
        $data = $form->getData() ?: [];
        $keys = [];
        $config = $form->getConfig();

        if ($config->hasAttribute('filter_value_keys')) {
            $keys = array_merge($data, $config->getAttribute('filter_value_keys'));
        }

        $values = ['value' => []];

        foreach ($keys as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values['value'][$key][] = $data[$key];

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $values['value'][$key][$k] = $v;
                    }
                }
            }
        }

        return $values;
    }
}
