<?php

namespace Snake;

final class Options
{
    public const int MIN_SIZE = 8;
    public const int MAX_SIZE = 200;

    public int $width = 24;
    public int $height = 16;
    public bool $ascii = false;
    public bool $help = false;
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
            } elseif ($arg === '--ascii') {
                $options->ascii = true;
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
            Terminal Snake — written in PHP, compiled to a native binary by TypePHP.

            Usage: {$program} [options]

              --width=N    board width in cells  (default 24, 8..200)
              --height=N   board height in cells (default 16, 8..200)
              --ascii      draw with plain ASCII (#, @, o, *) instead of Unicode blocks
              -h, --help   show this help

            Controls: arrows / WASD / hjkl move, P or Space pause, R restart, Q quit.

            TXT;
    }

    private function number(string $value, string $name, int $min, int $max): int
    {
        // No ctype_digit(): the ctype extension is not part of PHP Nano.
        if ($value === '' || strspn($value, '0123456789') !== strlen($value) || strlen($value) > 6) {
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
