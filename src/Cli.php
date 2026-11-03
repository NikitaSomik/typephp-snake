<?php

namespace Snake;

/**
 * Command line → benchmark or interactive game. Every runtime ends up here:
 * TypePHP and the interpreters through the global main() in main.php, elephc
 * directly (it reserves the name main() for its own entry point).
 */
final class Cli
{
    /**
     * @param list<string> $argv
     * @return int process exit code
     */
    public static function run(array $argv): int
    {
        $program = $argv !== [] ? $argv[0] : 'snake';
        $options = Options::fromArgv($argv);

        if ($options->help) {
            echo Options::usage($program);
            return 0;
        }
        if ($options->version) {
            echo 'snake ', Options::VERSION, "\n";
            return 0;
        }
        if ($options->error !== '') {
            echo $options->error, "\n\n", Options::usage($program);
            return 2;
        }
        if ($options->fibN > 0) {
            echo (new Benchmark())->fib($options->fibN);
            return 0;
        }
        if ($options->benchGames > 0) {
            echo (new Benchmark())->run($options->benchGames, $options->width, $options->height);
            return 0;
        }

        $game = new Game($options->width, $options->height, new SystemRandom());
        $app = new App(new Renderer($options->ascii), new KeyParser(), $options->autopilot ? new Autopilot() : null);

        return $app->run($game);
    }
}
