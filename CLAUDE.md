# CLAUDE.md

## Project: typephp-snake — a TypePHP pet project (terminal Snake)

**TypePHP** is an AOT compiler by the Swoole team: PHP → C++17 → a native executable (`--mode=bin`), a PHP extension or a shared library. The syntax is regular typed PHP; `int`/`float`/`bool` become C++ scalars. The project is young (autumn 2026), so compiler bugs are expected.

**Goal:** a small, finished portfolio project — Snake in the terminal, built into a single portable binary with `--nano` mode (no `libphp`, no Zend VM on the target machine). Room to grow later: Tetris or a roguelike.

## Commands

```bash
composer install
make build        # vendor/bin/tpc.php project.yml --nano -O2  → ./snake
make test         # PHPUnit (rules, key parser, options, renderer)
make parity       # the binary and the interpreter must produce the same checksum
make bench        # PHP vs PHP+JIT vs native binary
make play-php     # the same game on the interpreter (polyfill/terminal.php)
make autopilot    # watch the bot play; pass flags with ARGS="--ascii"
./snake --autopilot | --ascii | --width=N --height=N | --bench[=N]
```

The interactive game can be tested without a real terminal using Python's `pty` module: run the binary in a pseudo-terminal, send key bytes, answer `ESC[6n` with `ESC[rows;colsR` like a real terminal, and take the last complete frame (between `ESC[H` and `ESC[J`). Keep reading the pty while waiting for the process to exit, otherwise the game blocks on a full output buffer.

## Architecture

- `main.php` — global `main(int $argc, array $argv)`; TypePHP does not allow top-level code.
- `src/` — plain PHP with no terminal dependencies: `Game` (rules), `Snake` (body as packed int cells), `Renderer` (a frame as one ANSI string), `KeyParser`, `App` (game loop), `Autopilot`, `Benchmark`.
- `native/terminal.cc` + `terminal.stub.php` — termios raw mode, non-blocking `read()` + `nanosleep` for keys, a cursor position report (`ESC[6n`) for the window size. The C++ function `php_foo` is the PHP function `foo()`.
- `polyfill/terminal.php` — the same API in pure PHP (`stty`, `stream_select`) for `bin/snake.php`. This file is **not** part of `project.yml`.
- Build artifacts go to `build/` (set in `project.yml`) and are not committed.

## TypePHP / PHP Nano constraints (verified — don't step on these again)

- Dependencies are `dev-master` of `swoole/typephp`, `swoole/php-nano` and `swoole/phpx`, pinned in `composer.lock`. With the v0.8.1 / php-nano 1.0.1 releases the `standard` module was not registered in Nano builds.
- **No enums** — reading an enum case crashes the binary (`zend_enum_get_case`). Use a `final class` with `int` constants.
- **`int / int` is integer division** (as in C++). Write `1000.0 / $x` for a float, or `intdiv()` for an explicit integer.
- Inside a namespace, prefix global constants with `\`: `\PHP_INT_MAX`.
- Native code must not import `poll`, `select`, `ioctl`, processes, sockets, signals or `mmap` — the Nano audit fails the Linux build. The macOS audit misses these, so check Linux (CI or a container) after touching `native/`.
- Don't use SPL exceptions (`RuntimeException` etc.) — they break the Nano link step; use `Exception` only.
- No `ctype_*`, `mb_*`, `getenv` (compile error), `flush` (fails at runtime). No processes, signals, sockets or `include/require`.
- In hot loops, cast array elements to their type: `(int) $grid[$i]` — otherwise the variable becomes `php::Var`. Check the result in `build/src/*.cc`.
- `#[\Native]` classes are not usable yet: `$this->method($arg)` does not compile, native objects can't be passed to regular classes or interfaces, and static methods and array storage are not allowed.
- `ArrayAccess` + `??=` and `count()` with spreads in arrays are known compiler bugs — avoid them.

## Style

- PHP 8.4+: `final`/`readonly` classes, promoted properties, `private(set)`, typed constants, `match`.
- Code must behave the same in the binary and in the interpreter — run `make test` and `make parity` after changes.
