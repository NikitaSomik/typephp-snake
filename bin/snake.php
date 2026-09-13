#!/usr/bin/env php
<?php

// Runs the game on the regular PHP interpreter — handy while developing and a
// fair baseline to compare with the native binary built by TypePHP.

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../polyfill/terminal.php';
require __DIR__ . '/../main.php';

main($argc, $argv);
