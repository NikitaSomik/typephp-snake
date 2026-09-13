<?php

namespace Snake;

/** Logical keys produced by KeyParser. Directions reuse Direction values. */
final class Key
{
    public const int UP = Direction::UP;
    public const int RIGHT = Direction::RIGHT;
    public const int DOWN = Direction::DOWN;
    public const int LEFT = Direction::LEFT;
    public const int PAUSE = 10;
    public const int RESTART = 11;
    public const int QUIT = 12;

    public static function isDirection(int $key): bool
    {
        return $key >= Direction::UP && $key <= Direction::LEFT;
    }
}
