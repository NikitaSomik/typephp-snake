<?php

namespace Snake;

final class SystemRandom implements RandomSource
{
    public function below(int $max): int
    {
        return random_int(0, $max - 1);
    }
}
