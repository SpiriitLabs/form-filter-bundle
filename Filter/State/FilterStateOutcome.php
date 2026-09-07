<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\State;

/**
 * What a storage did with a filter state.
 */
enum FilterStateOutcome: string
{
    case Saved = 'saved';
    case NotStored = 'not_stored';
    case Restored = 'restored';
    case NothingStored = 'nothing_stored';
    case Cleared = 'cleared';
}
