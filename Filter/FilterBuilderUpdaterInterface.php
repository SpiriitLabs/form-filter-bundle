<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter;

use Symfony\Component\Form\FormInterface;

interface FilterBuilderUpdaterInterface
{
    /**
     * Build a filter query.
     *
     * @param  FormInterface $form
     * @param  object $filterBuilder
     * @param  string|null $alias
     */
    public function addFilterConditions(FormInterface $form, $filterBuilder, $alias = null);
}
