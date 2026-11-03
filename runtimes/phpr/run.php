#!/usr/bin/env php
<?php

// Runs the game on php-rust (phpr), a PHP interpreter written in Rust.
// Identical to runtimes/php/run.php except for the terminal layer, which
// works around phpr's buffered CLI output (see terminal.php).

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/terminal.php';
require __DIR__ . '/../../main.php';

term_output_start();

$arguments = $_SERVER['argv'] ?? [];
$arguments = is_array($arguments) ? array_values(array_filter($arguments, is_string(...))) : [];

main(count($arguments), $arguments);
