<?php

namespace Snake;

/**
 * Packs board coordinates into one int: cheap to store in arrays and to hash.
 * Boards are far smaller than 65536 cells per side.
 */
final class Cell
{
    public static function key(int $x, int $y): int
    {
        return ($y << 16) | $x;
    }

    public static function x(int $key): int
    {
        return $key & 0xFFFF;
    }

    public static function y(int $key): int
    {
        return $key >> 16;
    }
}
