<?php

namespace Snake;

/**
 * The second benchmark workload: naive recursive Fibonacci on plain ints.
 *
 * The game stresses objects, arrays and strings; this stresses calls and
 * integer arithmetic only, which is where an AOT compiler can lower PHP to
 * native code. Comparing runtimes on both workloads shows where each one wins.
 */
final class Fib
{
    public static function of(int $n): int
    {
        return $n < 2 ? $n : self::of($n - 1) + self::of($n - 2);
    }
}
