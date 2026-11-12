<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists($file = __DIR__ . '/../autoload.php')) {
    require_once $file;
} elseif (file_exists($file = __DIR__ . '/../autoload.php.dist')) {
    require_once $file;
}
