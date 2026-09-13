<?php

// Pure-PHP twin of native/terminal.cc, used when the game runs on the regular
// PHP interpreter (bin/snake.php). Same function names, same semantics.

/** State shared by the functions below. */
final class TerminalPolyfillState
{
    /** Output of `stty -g` taken before switching to raw mode; empty when not raw. */
    public static string $savedMode = '';

    /** @var array{int, int} rows and columns */
    public static array $size = [24, 80];

    public static int $sizeExpiresAt = 0;
}

function term_raw_on(): bool
{
    if (!stream_isatty(STDIN)) {
        return false;
    }
    TerminalPolyfillState::$savedMode = trim((string) shell_exec('stty -g'));
    shell_exec('stty -echo -icanon -isig -iexten -ixon -icrnl min 0 time 0');
    register_shutdown_function('term_raw_off');

    return true;
}

function term_raw_off(): void
{
    $saved = TerminalPolyfillState::$savedMode;
    if ($saved !== '') {
        shell_exec('stty ' . escapeshellarg($saved));
        TerminalPolyfillState::$savedMode = '';
    }
}

/** @return int<-1, 255> */
function term_read_byte(int $timeoutMs): int
{
    $read = [STDIN];
    $write = null;
    $except = null;
    $timeoutMs = max(0, $timeoutMs);
    if (stream_select($read, $write, $except, 0, $timeoutMs * 1000) < 1) {
        return -1;
    }
    $byte = fread(STDIN, 1);

    return $byte === false || $byte === '' ? -1 : ord($byte);
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
    if ($now >= TerminalPolyfillState::$sizeExpiresAt) {
        $size = explode(' ', trim((string) shell_exec('stty size 2>/dev/null')));
        if (count($size) === 2) {
            TerminalPolyfillState::$size = [(int) $size[0], (int) $size[1]];
        }
        TerminalPolyfillState::$sizeExpiresAt = $now + 500_000_000;
    }

    return TerminalPolyfillState::$size;
}

function term_flush(): void
{
    fflush(STDOUT);
}
