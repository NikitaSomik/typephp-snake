<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/bin', __DIR__ . '/polyfill'])
    ->append([__DIR__ . '/main.php', __FILE__]);

return (new PhpCsFixer\Config())
    ->setRules(['@PER-CS' => true])
    ->setUnsupportedPhpVersionAllowed(true)
    ->setFinder($finder);
