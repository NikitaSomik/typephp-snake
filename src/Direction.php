<?php

namespace Snake;

/**
 * Movement directions.
 *
 * Intentionally int constants instead of a native enum: reading enum cases
 * currently crashes TypePHP Nano binaries (zend_enum_get_case, see README).
 */
final class Direction
{
    public const int UP = 0;
    public const int RIGHT = 1;
    public const int DOWN = 2;
    public const int LEFT = 3;

    public static function dx(int $direction): int
    {
        return match ($direction) {
            self::RIGHT => 1,
            self::LEFT => -1,
            default => 0,
        };
    }

    public static function dy(int $direction): int
    {
        return match ($direction) {
            self::DOWN => 1,
            self::UP => -1,
            default => 0,
        };
    }

    public static function opposite(int $direction): int
    {
        return ($direction + 2) % 4;
    }
}
