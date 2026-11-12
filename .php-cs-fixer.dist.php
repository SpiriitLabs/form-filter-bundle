<?php

/*
 * This file is part of the spiriitlabs/form-filter-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'docs',
        'node_modules',
        'var',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'header_comment' => [
            'header' => <<<EOF
This file is part of the spiriitlabs/form-filter-bundle package.
Copyright (c) SpiriitLabs <https://www.spiriit.com/>
For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF,
        ],
    ])
    ->setFinder($finder)
;
