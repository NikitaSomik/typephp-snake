#!/usr/bin/env php
<?php

// Runs the game on the regular PHP interpreter: the reference every other
// runtime is compared with. `php -d opcache.jit=on …` runs the same file with JIT.

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/terminal.php';
require __DIR__ . '/../../main.php';

$arguments = $_SERVER['argv'] ?? [];
$arguments = is_array($arguments) ? array_values(array_filter($arguments, is_string(...))) : [];

main(count($arguments), $arguments);
