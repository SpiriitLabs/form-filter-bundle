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
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterStateStorageInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\State\FilterUrlGenerator;

class Autowired
{
    private FilterBuilderUpdaterInterface $filterBuilderUpdater;

    private FilterStateStorageInterface $filterStateStorage;

    private FilterUrlGenerator $filterUrlGenerator;

    public function __construct(
        FilterBuilderUpdaterInterface $filterBuilderUpdater,
        FilterStateStorageInterface $filterStateStorage,
        FilterUrlGenerator $filterUrlGenerator
    ) {
        $this->filterBuilderUpdater = $filterBuilderUpdater;
        $this->filterStateStorage = $filterStateStorage;
        $this->filterUrlGenerator = $filterUrlGenerator;
    }

    public function getFilterBuilderUpdater(): FilterBuilderUpdaterInterface
    {
        return $this->filterBuilderUpdater;
    }

    public function getFilterStateStorage(): FilterStateStorageInterface
    {
        return $this->filterStateStorage;
    }

    public function getFilterUrlGenerator(): FilterUrlGenerator
    {
        return $this->filterUrlGenerator;
    }
}
