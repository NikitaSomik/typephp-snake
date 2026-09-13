<?php

namespace Snake;

interface RandomSource
{
    /** Returns an integer in the range [0, $max). */
    public function below(int $max): int;
}
