<?php

namespace Snake;

final readonly class Point
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}

    public function step(int $direction): Point
    {
        return new Point($this->x + Direction::dx($direction), $this->y + Direction::dy($direction));
    }

    public function equals(Point $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    public function within(int $width, int $height): bool
    {
        return $this->x >= 0 && $this->y >= 0 && $this->x < $width && $this->y < $height;
    }

    public function key(): int
    {
        return Cell::key($this->x, $this->y);
    }
}
