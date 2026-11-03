<?php

// Terminal layer for php-rust (phpr): the same API as runtimes/php/terminal.php,
// with two workarounds for phpr's CLI.
//
// 1. phpr collects everything written through echo, STDOUT or php://stdout and
//    prints it only when the script ends. Output is captured with an output
//    buffer instead and written to /dev/stdout (a plain file for phpr) on every
//    term_flush() and at shutdown.
// 2. stream_select() on STDIN does not see bytes already pulled into the
//    stream's read buffer, so reading one byte at a time loses the rest of an
//    escape sequence. All available bytes are read at once and queued.

final class PhprTerminalState
{
    /** Output of `stty -g` taken before switching to raw mode; empty when not raw. */
    public static string $savedMode = '';

    /** @var array{int, int} rows and columns */
    public static array $size = [24, 80];

    public static int $sizeExpiresAt = 0;

    /** @var resource|null */
    public static $out = null;

    /** Bytes read from STDIN but not consumed yet. */
    public static string $pending = '';
}

function term_output_start(): void
{
    $out = fopen('/dev/stdout', 'w');
    if ($out === false) {
        return;
    }
    PhprTerminalState::$out = $out;
    ob_start();
    register_shutdown_function('term_flush');
}

function term_raw_on(): bool
{
    if (!stream_isatty(STDIN)) {
        return false;
    }
    PhprTerminalState::$savedMode = trim((string) shell_exec('stty -g'));
    shell_exec('stty -echo -icanon -isig -iexten -ixon -icrnl min 0 time 0');
    register_shutdown_function('term_raw_off');

    return true;
}

function term_raw_off(): void
{
    $saved = PhprTerminalState::$savedMode;
    if ($saved !== '') {
        shell_exec('stty ' . escapeshellarg($saved));
        PhprTerminalState::$savedMode = '';
    }
}

/** @return int<-1, 255> */
function term_read_byte(int $timeoutMs): int
{
    if (PhprTerminalState::$pending === '') {
        $read = [STDIN];
        $write = null;
        $except = null;
        if (stream_select($read, $write, $except, 0, max(0, $timeoutMs) * 1000) < 1) {
            return -1;
        }
        $bytes = fread(STDIN, 64);
        if ($bytes === false || $bytes === '') {
            return -1;
        }
        PhprTerminalState::$pending = $bytes;
    }
    $byte = ord(PhprTerminalState::$pending[0]);
    PhprTerminalState::$pending = substr(PhprTerminalState::$pending, 1);

    return $byte;
}

function term_cols(): int
{
    return term_size()[1];
}

function term_rows(): int
{
    return term_size()[0];
}

/**
 * `stty size` spawns a process, so the result is cached for half a second.
 *
 * @return array{int, int} rows and columns
 */
function term_size(): array
{
    $now = hrtime(true);
    if ($now >= PhprTerminalState::$sizeExpiresAt) {
        $size = explode(' ', trim((string) shell_exec('stty size 2>/dev/null')));
        if (count($size) === 2) {
            PhprTerminalState::$size = [(int) $size[0], (int) $size[1]];
        }
        PhprTerminalState::$sizeExpiresAt = $now + 500_000_000;
    }

    return PhprTerminalState::$size;
}

function term_flush(): void
{
    $out = PhprTerminalState::$out;
    if ($out === null) {
        fflush(STDOUT);
        return;
    }
    $chunk = '';
    while (ob_get_level() > 0) {
        $chunk .= (string) ob_get_clean();
    }
    if ($chunk !== '') {
        fwrite($out, $chunk);
        fflush($out);
    }
    ob_start();
}
