<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Event;

/**
 * Available filter events.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FilterEvents
{
    public const PREPARE = 'spiriit_filter.prepare';

    public const APPLIED = 'spiriit_filter.applied';
}
