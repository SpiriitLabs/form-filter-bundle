<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Stubs;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

class Autowired
{
    private FilterBuilderUpdaterInterface $filterBuilderUpdater;

    public function __construct(
        FilterBuilderUpdaterInterface $filterBuilderUpdater
    ) {
        $this->filterBuilderUpdater = $filterBuilderUpdater;
    }

    public function getFilterBuilderUpdater(): FilterBuilderUpdaterInterface
    {
        return $this->filterBuilderUpdater;
    }
}
