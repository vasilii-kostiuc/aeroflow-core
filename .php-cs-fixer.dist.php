<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->append([
        __FILE__,
    ])
    ->ignoreDotFiles(false)
    ->ignoreVCS(true);

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_method_casing' => false,
        'phpdoc_to_comment' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder);
