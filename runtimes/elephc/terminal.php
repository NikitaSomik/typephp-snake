<?php

// Terminal layer for elephc: not ported yet, so the elephc build runs the
// headless benchmarks (--bench, --bench-fib) only. These functions satisfy the
// terminal contract (runtimes/typephp/native/terminal.stub.php) and make the
// interactive game exit with a message instead of failing to compile.

function term_raw_on(): bool
{
    echo "Interactive play is not ported to elephc yet; try --bench or --bench-fib.\n";

    return false;
}

function term_raw_off(): void {}

/** @return int<-1, 255> */
function term_read_byte(int $timeoutMs): int
{
    return -1;
}

function term_cols(): int
{
    return 80;
}

function term_rows(): int
{
    return 24;
}

function term_flush(): void {}
