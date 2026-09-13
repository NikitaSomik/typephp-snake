<?php

// Declarations of the C++ functions implemented in native/terminal.cc.
// The compiled binary links the C++ code; bin/snake.php loads a pure-PHP
// implementation of the same API from polyfill/terminal.php instead.

function term_raw_on(): bool {}

function term_raw_off(): void {}

/** @return int<-1, 255> the byte, or -1 when nothing arrived in time */
function term_read_byte(int $timeoutMs): int {}

function term_cols(): int {}

function term_rows(): int {}

function term_flush(): void {}
