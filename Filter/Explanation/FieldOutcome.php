<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Explanation;

/**
 * What happened to a form field while the filter conditions were built.
 */
enum FieldOutcome: string
{
    case Applied = 'applied';
    case NoCondition = 'no_condition';
    case NoListener = 'no_listener';
    case Disabled = 'disabled';
}
