/*
 * Minimal POSIX terminal layer for the Snake game.
 *
 * PHP Nano deliberately ships without processes, signals and ioctl-style
 * APIs, so `stty`, `pcntl` and `posix` are unavailable inside the compiled
 * binary. Its dependency audit also rejects poll/select and ioctl, so this
 * file sticks to termios, read/write, clock_gettime and nanosleep:
 *
 *  - keys are read with a non-blocking read() in raw mode plus short sleeps;
 *  - the window size comes from a cursor position report: the cursor is sent
 *    to the far bottom-right corner and the terminal answers ESC[rows;colsR.
 *
 * Every function is exposed to PHP through native/terminal.stub.php; TypePHP
 * maps a C++ `php_<name>` function to the PHP function `<name>`.
 */
#include <phpx.h>

#include <cstdio>
#include <cstdlib>
#include <ctime>

#include <termios.h>
#include <unistd.h>

using namespace php;

namespace {

struct termios original_mode;
bool raw_enabled = false;
bool restore_registered = false;

// Keys typed while a window size query was waiting for the terminal's answer.
unsigned char pending_keys[64];
int pending_count = 0;
int pending_head = 0;

int cached_cols = 80;
int cached_rows = 24;
long long size_checked_at = -1'000'000;
const long long SIZE_CACHE_MS = 500;
const long long SIZE_QUERY_TIMEOUT_MS = 150;

long long now_ms() {
    struct timespec ts;
    clock_gettime(CLOCK_MONOTONIC, &ts);
    return static_cast<long long>(ts.tv_sec) * 1000 + ts.tv_nsec / 1'000'000;
}

void sleep_ms(long ms) {
    struct timespec ts = {0, ms * 1'000'000L};
    nanosleep(&ts, nullptr);
}

// Raw mode uses VMIN=0/VTIME=0, so read() returns immediately when no byte is waiting.
int read_byte_now() {
    unsigned char byte;
    return read(STDIN_FILENO, &byte, 1) == 1 ? static_cast<int>(byte) : -1;
}

void remember_key(unsigned char byte) {
    if (pending_count < static_cast<int>(sizeof(pending_keys))) {
        pending_keys[(pending_head + pending_count) % sizeof(pending_keys)] = byte;
        pending_count++;
    }
}

void remember_keys(const unsigned char *bytes, int count) {
    for (int i = 0; i < count; i++) {
        remember_key(bytes[i]);
    }
}

void restore_terminal() {
    if (raw_enabled) {
        tcsetattr(STDIN_FILENO, TCSAFLUSH, &original_mode);
        raw_enabled = false;
    }
}

bool is_report_byte(unsigned char byte) {
    return (byte >= '0' && byte <= '9') || byte == ';';
}

// Sends the cursor far away and parses the ESC[rows;colsR reply. Anything else
// that arrives meanwhile is a key press and is kept for php_term_read_byte().
bool query_window_size(int &rows, int &cols) {
    static const char request[] = "\x1b[999;999H\x1b[6n";
    std::fflush(stdout);
    if (write(STDOUT_FILENO, request, sizeof(request) - 1) != static_cast<ssize_t>(sizeof(request) - 1)) {
        return false;
    }

    unsigned char reply[16];
    int length = 0;
    long long deadline = now_ms() + SIZE_QUERY_TIMEOUT_MS;
    while (now_ms() < deadline) {
        int value = read_byte_now();
        if (value < 0) {
            sleep_ms(1);
            continue;
        }
        unsigned char byte = static_cast<unsigned char>(value);
        if (length == 0 && byte != 0x1b) {
            remember_key(byte);
            continue;
        }
        reply[length++] = byte;
        bool still_a_report = (length == 1)
            || (length == 2 && byte == '[')
            || (length > 2 && (is_report_byte(byte) || byte == 'R'));
        if (!still_a_report || length == static_cast<int>(sizeof(reply))) {
            remember_keys(reply, length);   // e.g. an arrow key, not our answer
            length = 0;
            continue;
        }
        if (byte == 'R') {
            int r = 0, c = 0;
            int i = 2;
            while (i < length && reply[i] != ';') {
                r = r * 10 + (reply[i++] - '0');
            }
            i++;
            while (i < length && reply[i] != 'R') {
                c = c * 10 + (reply[i++] - '0');
            }
            if (r > 0 && c > 0) {
                rows = r;
                cols = c;
                return true;
            }
            return false;
        }
    }
    remember_keys(reply, length);
    return false;
}

void refresh_window_size() {
    if (!raw_enabled || now_ms() - size_checked_at < SIZE_CACHE_MS) {
        return;
    }
    int rows, cols;
    if (query_window_size(rows, cols)) {
        cached_rows = rows;
        cached_cols = cols;
    }
    size_checked_at = now_ms();
}

}  // namespace

// Switches stdin to raw mode: no echo, no line buffering, no signal keys.
// Ctrl+C therefore arrives as byte 3 and is handled by the game itself.
Bool php_term_raw_on() {
    if (raw_enabled) {
        return true;
    }
    if (!isatty(STDIN_FILENO) || tcgetattr(STDIN_FILENO, &original_mode) != 0) {
        return false;
    }

    struct termios raw = original_mode;
    raw.c_lflag &= ~(ECHO | ICANON | ISIG | IEXTEN);
    raw.c_iflag &= ~(IXON | ICRNL);
    raw.c_cc[VMIN] = 0;
    raw.c_cc[VTIME] = 0;
    if (tcsetattr(STDIN_FILENO, TCSAFLUSH, &raw) != 0) {
        return false;
    }

    raw_enabled = true;
    if (!restore_registered) {
        // Safety net: never leave the user's shell in raw mode on exit.
        std::atexit(restore_terminal);
        restore_registered = true;
    }
    return true;
}

void php_term_raw_off() {
    restore_terminal();
}

// Waits up to `timeout_ms` for a single byte; returns -1 when nothing arrived.
Int php_term_read_byte(Int timeout_ms) {
    if (pending_count > 0) {
        unsigned char byte = pending_keys[pending_head];
        pending_head = (pending_head + 1) % sizeof(pending_keys);
        pending_count--;
        return byte;
    }

    long long deadline = now_ms() + (timeout_ms > 0 ? timeout_ms : 0);
    while (true) {
        int byte = read_byte_now();
        if (byte >= 0) {
            return byte;
        }
        long long left = deadline - now_ms();
        if (left <= 0) {
            return -1;
        }
        sleep_ms(left < 2 ? left : 2);
    }
}

Int php_term_cols() {
    refresh_window_size();
    return cached_cols;
}

Int php_term_rows() {
    refresh_window_size();
    return cached_rows;
}

void php_term_flush() {
    std::fflush(stdout);
}
