<?php

namespace Snake;

/**
 * Pure game rules: no terminal, no clock. The loop in App calls tick() at
 * tickIntervalMs(), which keeps the rules deterministic and unit-testable.
 */
final class Game
{
    public const int RUNNING = 0;
    public const int PAUSED = 1;
    public const int OVER = 2;
    public const int WON = 3;

    public const int START_LENGTH = 4;
    public const int POINTS_PER_FOOD = 10;

    private const int START_INTERVAL_MS = 140;
    private const int MIN_INTERVAL_MS = 55;
    private const int SPEEDUP_PER_FOOD_MS = 4;
    private const int MAX_QUEUED_TURNS = 3;

    public private(set) Snake $snake;
    public private(set) ?Point $food = null;
    public private(set) int $score = 0;
    public private(set) int $state = self::RUNNING;
    public private(set) int $ticks = 0;

    /** @var list<int> turns pressed faster than the snake moves */
    private array $turnQueue = [];

    public function __construct(
        public readonly int $width,
        public readonly int $height,
        private readonly RandomSource $random,
    ) {
        $this->snake = $this->spawnSnake();
        $this->placeFood();
    }

    public function restart(): void
    {
        $this->snake = $this->spawnSnake();
        $this->score = 0;
        $this->ticks = 0;
        $this->state = self::RUNNING;
        $this->turnQueue = [];
        $this->placeFood();
    }

    /** Queues a turn; U-turns and repeated presses are ignored. */
    public function turn(int $direction): void
    {
        if ($this->state !== self::RUNNING || count($this->turnQueue) >= self::MAX_QUEUED_TURNS) {
            return;
        }
        $current = $this->turnQueue === []
            ? $this->snake->direction
            : $this->turnQueue[count($this->turnQueue) - 1];
        if ($direction === $current || $direction === Direction::opposite($current)) {
            return;
        }
        $this->turnQueue[] = $direction;
    }

    public function togglePause(): void
    {
        if ($this->state === self::RUNNING) {
            $this->state = self::PAUSED;
        } elseif ($this->state === self::PAUSED) {
            $this->state = self::RUNNING;
        }
    }

    public function isFinished(): bool
    {
        return $this->state === self::OVER || $this->state === self::WON;
    }

    /** Advances the world by one step. */
    public function tick(): void
    {
        if ($this->state !== self::RUNNING) {
            return;
        }
        if ($this->turnQueue !== []) {
            // Not array_shift($this->turnQueue): TypePHP passes the property by reference
            // and leaves it as a PHP reference, so the next `$this->turnQueue = []` in
            // restart() segfaults the binary (zend_try_assign_typed_ref).
            $this->snake->turn($this->turnQueue[0]);
            $this->turnQueue = array_slice($this->turnQueue, 1);
        }

        $next = $this->snake->nextHead();
        $eats = $this->food !== null && $next->equals($this->food);
        if (!$this->inside($next) || $this->snake->wouldCollide($next, $eats)) {
            $this->state = self::OVER;
            return;
        }

        $this->snake->advance($eats);
        $this->ticks++;
        if ($eats) {
            $this->score += self::POINTS_PER_FOOD;
            $this->placeFood();
        }
    }

    public function tickIntervalMs(): int
    {
        $eaten = intdiv($this->score, self::POINTS_PER_FOOD);

        return max(self::MIN_INTERVAL_MS, self::START_INTERVAL_MS - $eaten * self::SPEEDUP_PER_FOOD_MS);
    }

    public function inside(Point $point): bool
    {
        return $point->within($this->width, $this->height);
    }

    private function spawnSnake(): Snake
    {
        $headX = intdiv($this->width, 2) + intdiv(self::START_LENGTH, 2);

        return new Snake($headX, intdiv($this->height, 2), self::START_LENGTH, Direction::RIGHT);
    }

    private function placeFood(): void
    {
        $free = $this->width * $this->height - $this->snake->length();
        if ($free <= 0) {
            $this->food = null;
            $this->state = self::WON;
            return;
        }

        // Pick the n-th free cell: uniform and always terminates, even on a nearly full board.
        $target = $this->random->below($free);
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->snake->occupiesKey(Cell::key($x, $y))) {
                    continue;
                }
                if ($target === 0) {
                    $this->food = new Point($x, $y);
                    return;
                }
                $target--;
            }
        }
    }
}
