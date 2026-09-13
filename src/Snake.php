<?php

namespace Snake;

/**
 * The body is stored as packed cell keys (see Cell) rather than Point objects:
 * ints are cheap to keep in arrays for both the PHP VM and compiled code.
 * Points exist only at the API edge.
 */
final class Snake
{
    /** @var list<int> cell keys, head first */
    private array $body = [];

    /** @var array<int, bool> cell key => true, for O(1) collision checks */
    private array $occupied = [];

    public private(set) int $direction;

    /** Creates a straight snake whose head is at ($headX, $headY), moving in $direction. */
    public function __construct(int $headX, int $headY, int $length, int $direction)
    {
        $back = Direction::opposite($direction);
        for ($i = 0; $i < $length; $i++) {
            $key = Cell::key($headX + Direction::dx($back) * $i, $headY + Direction::dy($back) * $i);
            $this->body[] = $key;
            $this->occupied[$key] = true;
        }
        $this->direction = $direction;
    }

    public function head(): Point
    {
        $key = (int) $this->body[0];

        return new Point(Cell::x($key), Cell::y($key));
    }

    public function length(): int
    {
        return count($this->body);
    }

    /** @return list<int> cell keys, head first */
    public function cells(): array
    {
        return $this->body;
    }

    public function occupies(Point $point): bool
    {
        return isset($this->occupied[$point->key()]);
    }

    public function occupiesKey(int $key): bool
    {
        return isset($this->occupied[$key]);
    }

    public function turn(int $direction): void
    {
        $this->direction = $direction;
    }

    public function nextHead(): Point
    {
        return $this->head()->step($this->direction);
    }

    /** Would moving into $point bite the snake? The tail cell is free unless the snake grows. */
    public function wouldCollide(Point $point, bool $growing): bool
    {
        $key = $point->key();
        if (!isset($this->occupied[$key])) {
            return false;
        }

        return $growing || $key !== (int) $this->body[count($this->body) - 1];
    }

    public function advance(bool $grow): void
    {
        $head = $this->nextHead()->key();
        if (!$grow) {
            $tail = (int) array_pop($this->body);
            unset($this->occupied[$tail]);
        }
        array_unshift($this->body, $head);
        $this->occupied[$head] = true;
    }
}
