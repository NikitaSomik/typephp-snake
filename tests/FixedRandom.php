<?php

namespace Snake\Tests;

use Snake\RandomSource;

/** Always picks the n-th free cell, which makes food placement predictable. */
final class FixedRandom implements RandomSource
{
    public function __construct(private readonly int $value = 0)
    {
    }

    public function below(int $max): int
    {
        return min($this->value, $max - 1);
    }
}
