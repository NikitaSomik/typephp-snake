<?php

use Snake\Cli;

/**
 * Entry point. TypePHP binaries start at a global main() — top-level code is
 * not allowed — and the interpreters (runtimes/php, runtimes/phpr) call this
 * very function. The work happens in Snake\Cli, which elephc calls directly.
 *
 * @param list<string> $argv
 */
function main(int $argc, array $argv): void
{
    $code = Cli::run($argv);
    if ($code !== 0) {
        exit($code);
    }
}
