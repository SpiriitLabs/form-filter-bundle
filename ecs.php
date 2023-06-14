<?php

declare(strict_types=1);

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $config): void {
    $header = <<<EOF
This file is part of the composer-write-changelogs project.

(c) Dev Spiriit <dev@spiriit.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

    $config->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => $header
    ]);

    $config->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => $header
    ]);

    $config->parallel();
    $config->paths([__DIR__]);
    $config->skip([
        __DIR__ . '/.github',
        __DIR__ . '/vendor',
    ]);
};
