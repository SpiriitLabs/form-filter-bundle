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

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withSkip([
        __DIR__ . '/.github',
        __DIR__ . '/DependencyInjection/Configuration.php',
        __DIR__ . '/vendor',
        // typing this protected method would break the subclasses that override it in userland
        ReturnNullableTypeRector::class => [__DIR__ . '/Filter/FilterBuilderUpdater.php'],
        ReturnTypeFromStrictTypedPropertyRector::class => [__DIR__ . '/Filter/FilterBuilderUpdater.php'],
    ])
    ->withPaths([
        __DIR__
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withSets([SetList::PHP_84])
    ->withImportNames()
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true
    );
