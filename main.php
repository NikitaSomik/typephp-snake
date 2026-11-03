<?php

use Snake\App;
use Snake\Autopilot;
use Snake\Benchmark;
use Snake\Game;
use Snake\KeyParser;
use Snake\Options;
use Snake\Renderer;
use Snake\SystemRandom;

/**
 * Entry point. TypePHP binaries start at a global main() — top-level code is
 * not allowed — and every runtime under runtimes/ calls this very function.
 *
 * @param list<string> $argv
 */
function main(int $argc, array $argv): void
{
    $program = $argc > 0 ? $argv[0] : 'snake';
    $options = Options::fromArgv($argv);

    if ($options->help) {
        echo Options::usage($program);
        return;
    }
    if ($options->version) {
        echo 'snake ', Options::VERSION, "\n";
        return;
    }
    if ($options->error !== '') {
        echo $options->error, "\n\n", Options::usage($program);
        exit(2);
    }
    if ($options->fibN > 0) {
        echo (new Benchmark())->fib($options->fibN);
        return;
    }
    if ($options->benchGames > 0) {
        echo (new Benchmark())->run($options->benchGames, $options->width, $options->height);
        return;
    }

    $game = new Game($options->width, $options->height, new SystemRandom());
    $app = new App(new Renderer($options->ascii), new KeyParser(), $options->autopilot ? new Autopilot() : null);
    $code = $app->run($game);
    if ($code !== 0) {
        exit($code);
    }
}
