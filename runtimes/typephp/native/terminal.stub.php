<?php

// The terminal contract: six functions the game calls to talk to the terminal.
// For TypePHP they are implemented in C++ (terminal.cc next to this file).
// Every other runtime implements the same functions in runtimes/<name>/terminal.php.

function term_raw_on(): bool {}

function term_raw_off(): void {}

/** @return int<-1, 255> the byte, or -1 when nothing arrived in time */
function term_read_byte(int $timeoutMs): int {}

function term_cols(): int {}

function term_rows(): int {}

function term_flush(): void {}
