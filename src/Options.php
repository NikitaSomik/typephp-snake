<?php

namespace Snake;

final class Options
{
    /** Keep in sync with project.yml; the release workflow checks it against the tag. */
    public const string VERSION = '1.0.1';

    public const int MIN_SIZE = 8;
    public const int MAX_SIZE = 200;

    public int $width = 24;
    public int $height = 16;
    public bool $ascii = false;
    public bool $autopilot = false;
    public bool $help = false;
    public bool $version = false;
    /** Number of autopilot games to simulate; 0 means play interactively. */
    public int $benchGames = 0;
    /** Argument of the recursive fib() benchmark; 0 means do not run it. */
    public int $fibN = 0;
    public string $error = '';

    /** @param array<int, string> $argv */
    public static function fromArgv(array $argv): Options
    {
        $options = new Options();
        $count = count($argv);
        for ($i = 1; $i < $count; $i++) {
            $arg = $argv[$i];
            if ($arg === '-h' || $arg === '--help') {
                $options->help = true;
            } elseif ($arg === '--version') {
                $options->version = true;
            } elseif ($arg === '--ascii') {
                $options->ascii = true;
            } elseif ($arg === '--autopilot') {
                $options->autopilot = true;
            } elseif ($arg === '--bench') {
                $options->benchGames = 200;
            } elseif (str_starts_with($arg, '--bench=')) {
                $options->benchGames = $options->number(substr($arg, 8), '--bench', 1, 100000);
            } elseif ($arg === '--bench-fib') {
                $options->fibN = 32;
            } elseif (str_starts_with($arg, '--bench-fib=')) {
                $options->fibN = $options->number(substr($arg, 12), '--bench-fib', 1, 40);
            } elseif (str_starts_with($arg, '--width=')) {
                $options->width = $options->number(substr($arg, 8), '--width', self::MIN_SIZE, self::MAX_SIZE);
            } elseif (str_starts_with($arg, '--height=')) {
                $options->height = $options->number(substr($arg, 9), '--height', self::MIN_SIZE, self::MAX_SIZE);
            } else {
                $options->error = "Unknown option: {$arg}";
            }
        }

        return $options;
    }

    public static function usage(string $program): string
    {
        return <<<TXT
            Terminal Snake — written in PHP, one source for several PHP runtimes and compilers.

            Usage: {$program} [options]

              --width=N        board width in cells  (default 24, 8..200)
              --height=N       board height in cells (default 16, 8..200)
              --bench[=N]      headless benchmark: autopilot plays N seeded games (default 200)
              --bench-fib[=N]  headless benchmark: recursive fib(N) on ints (default 32)
              --ascii          draw with plain ASCII (#, @, o, *) instead of Unicode blocks
              --autopilot      sit back and watch a bot play (keys still work: P, R, Q)
              --version        show the version
              -h, --help       show this help

            Controls: arrows / WASD / hjkl move, P or Space pause, R restart, Q quit.

            TXT;
    }

    /**
     * A plain loop on purpose: ctype_digit() is not part of PHP Nano (TypePHP)
     * and strspn() is not available in elephc.
     */
    private static function isDigits(string $value): bool
    {
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($value[$i]);
            if ($byte < 48 || $byte > 57) {
                return false;
            }
        }

        return $length > 0;
    }

    private function number(string $value, string $name, int $min, int $max): int
    {
        if (!self::isDigits($value) || strlen($value) > 6) {
            $this->error = "{$name} expects a number, got '{$value}'";
            return $min;
        }
        $number = (int) $value;
        if ($number < $min || $number > $max) {
            $this->error = sprintf('%s must be between %d and %d', $name, $min, $max);
        }

        return $number;
    }
}
