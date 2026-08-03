<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/support')
    ->exclude('vendor')
    ->exclude('model/Test.php')
    ->notPath('process/*')
    ->notPath('queue/*');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
