<?php

// Entry point for elephc (https://elephc.dev), an ahead-of-time compiler that
// turns PHP into native machine code without the Zend engine.
//
// elephc compiles one file and resolves `require` at compile time, so the game
// is pulled in file by file here instead of through Composer's autoloader.
// Build: elephc runtimes/elephc/snake.php  →  runtimes/elephc/snake

require __DIR__ . '/terminal.php';
require __DIR__ . '/../../src/RandomSource.php';
require __DIR__ . '/../../src/SeededRandom.php';
require __DIR__ . '/../../src/SystemRandom.php';
require __DIR__ . '/../../src/Cell.php';
require __DIR__ . '/../../src/Direction.php';
require __DIR__ . '/../../src/Key.php';
require __DIR__ . '/../../src/Point.php';
require __DIR__ . '/../../src/Snake.php';
require __DIR__ . '/../../src/Game.php';
require __DIR__ . '/../../src/Autopilot.php';
require __DIR__ . '/../../src/Renderer.php';
require __DIR__ . '/../../src/KeyParser.php';
require __DIR__ . '/../../src/Fib.php';
require __DIR__ . '/../../src/Benchmark.php';
require __DIR__ . '/../../src/Options.php';
require __DIR__ . '/../../src/App.php';
require __DIR__ . '/../../main.php';

/** @var list<string> $argv */
main(count($argv), $argv);
