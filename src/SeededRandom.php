<?php

namespace Snake;

/**
 * Deterministic LCG, so a benchmark replays the exact same games on the PHP
 * interpreter and in the native binary. The state stays below 2^31, so the
 * multiplication never overflows 64-bit integers on either side.
 */
final class SeededRandom implements RandomSource
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed & 0x7FFFFFFF;
    }

    public function below(int $max): int
    {
        $this->state = ($this->state * 1103515245 + 12345) & 0x7FFFFFFF;

        return ($this->state >> 16) % $max;
    }
}
