<?php

// Pure-PHP twin of native/terminal.cc, used when the game runs on the regular
// PHP interpreter (bin/snake.php). Same function names, same semantics.

function term_raw_on(): bool
{
    if (!stream_isatty(STDIN)) {
        return false;
    }
    $GLOBALS['__snake_stty'] = trim((string) shell_exec('stty -g'));
    shell_exec('stty -echo -icanon -isig -iexten -ixon -icrnl min 0 time 0');
    register_shutdown_function('term_raw_off');

    return true;
}

function term_raw_off(): void
{
    $saved = $GLOBALS['__snake_stty'] ?? '';
    if ($saved !== '') {
        shell_exec('stty ' . escapeshellarg($saved));
        $GLOBALS['__snake_stty'] = '';
    }
}

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
    static $cached = [24, 80];
    static $expiresAt = 0;

    $now = hrtime(true);
    if ($now >= $expiresAt) {
        $size = explode(' ', trim((string) shell_exec('stty size 2>/dev/null')));
        if (count($size) === 2) {
            $cached = [(int) $size[0], (int) $size[1]];
        }
        $expiresAt = $now + 500_000_000;
    }

    return $cached;
}

function term_flush(): void
{
    fflush(STDOUT);
}
