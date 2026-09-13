<?php

use Snake\App;
use Snake\Autopilot;
use Snake\Game;
use Snake\KeyParser;
use Snake\Options;
use Snake\Renderer;
use Snake\SystemRandom;

/**
 * Entry point. TypePHP binaries start at a global main() — top-level code is
 * not allowed — and bin/snake.php calls the very same function on regular PHP.
 */
function main(int $argc, array $argv): void
{
    $program = $argc > 0 ? $argv[0] : 'snake';
    $options = Options::fromArgv($argv);

    if ($options->help) {
        echo Options::usage($program);
        return;
    }
    if ($options->error !== '') {
        echo $options->error, "\n\n", Options::usage($program);
        exit(2);
    }

    $game = new Game($options->width, $options->height, new SystemRandom());
    $app = new App(new Renderer($options->ascii), new KeyParser(), $options->autopilot ? new Autopilot() : null);
    $code = $app->run($game);
    if ($code !== 0) {
        exit($code);
    }
}
