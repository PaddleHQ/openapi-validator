<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('resources')
    ->exclude('storage')
    ->exclude('vendor')
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'trailing_comma_in_multiline_array' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
