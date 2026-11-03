# CLAUDE.md

## Project: typephp-snake — terminal Snake as a testbed for PHP runtimes and compilers

**TypePHP** is an AOT compiler by the Swoole team: PHP → C++17 → a native executable (`--mode=bin`), a PHP extension or a shared library. The syntax is regular typed PHP; `int`/`float`/`bool` become C++ scalars. The project is young (autumn 2026), so compiler bugs are expected.

**Goal:** a small, finished portfolio project — Snake in the terminal, built into a single portable binary with `--nano` mode (no `libphp`, no Zend VM on the target machine) — and a fair comparison of PHP runtimes on the same code: the PHP interpreter (± JIT, the reference), TypePHP (primary), php-rust `phpr` and elephc (both experimental). See `runtimes/README.md` for the rules.

## Commands

```bash
composer install
make build        # vendor/bin/tpc.php project.yml --nano -O2  → ./snake
make test         # PHPUnit (rules, key parser, options, renderer, autopilot)
make analyse      # PHPStan, level max (runtimes/typephp/native/terminal.stub.php is a stub file, not analysed)
make cs           # PHP-CS-Fixer dry run, PER-CS; make cs-fix applies it
make parity       # bench/parity.sh: every available runtime must match php's checksums (RUNTIMES="typephp")
make smoke        # tests/tty/smoke.py: play both versions in a pseudo-terminal (turn, pause, game over, restart, quit)
make bench        # bench/run.sh: every available runtime, both workloads, one Markdown table (SAVE=1 → bench/results/)
make play-php     # the same game on the interpreter (runtimes/php)
make play-phpr    # the same game on php-rust (runtimes/phpr; PHPR=/path/to/phpr)
make build-elephc # elephc runtimes/elephc/snake.php → runtimes/elephc/snake (benchmarks only)
make autopilot    # watch the bot play; pass flags with ARGS="--ascii"
./snake --autopilot | --ascii | --width=N --height=N | --bench[=N] | --bench-fib[=N]
```

`tests/tty/smoke.py` tests the interactive game without a real terminal: it runs the game in a pseudo-terminal, sends key bytes, answers `ESC[6n` with `ESC[rows;colsR` like a real terminal and reads the last complete frame (between `ESC[H` and `ESC[J`). Keep reading the pty while waiting for the process to exit, otherwise the game blocks on a full output buffer.

## Releases

Bump `Options::VERSION` and `version` in `project.yml`, then push a tag `vX.Y.Z` on main (the workflow fails if `./snake --version` differs from the tag): `.github/workflows/release.yml` builds Linux x64/arm64 (on Ubuntu 22.04, for glibc compatibility) and macOS arm64, runs the smoke test, attests each archive and publishes them plus `SHA256SUMS` to a GitHub Release. Pull requests that change the workflow run the builds without publishing.

## Architecture

- `main.php` — global `main(int $argc, array $argv)`; TypePHP does not allow top-level code. It only calls `Snake\Cli::run()` (options → benchmark or game), which elephc calls directly because it reserves `main()`.
- `src/` — plain PHP with no terminal dependencies, shared by every runtime: `Game` (rules), `Snake` (body as packed int cells), `Renderer` (a frame as one ANSI string), `KeyParser`, `App` (game loop), `Autopilot`, `Benchmark`, `Fib`.
- `runtimes/<name>/` — everything specific to one runtime: entry point, terminal layer, a README with its known limits. The terminal contract is the six functions in `runtimes/typephp/native/terminal.stub.php`.
- `runtimes/typephp/native/terminal.cc` — termios raw mode, non-blocking `read()` + `nanosleep` for keys, a cursor position report (`ESC[6n`) for the window size. The C++ function `php_foo` is the PHP function `foo()`.
- `runtimes/php/terminal.php` — the same API in pure PHP (`stty`, `stream_select`) for `runtimes/php/run.php`; `runtimes/phpr/terminal.php` adds php-rust workarounds; `runtimes/elephc/terminal.php` is a stub (benchmarks only). None of them is part of `project.yml`.
- `runtimes/elephc/snake.php` — elephc compiles one file and resolves `require` at compile time, so it requires each `src/` file explicitly. **Add new `src/` files there too.**
- `bench/runtimes.sh` — how to start each runtime; `bench/run.sh` and `bench/parity.sh` use it.
- Build artifacts go to `build/` (set in `project.yml`) and are not committed.

## TypePHP / PHP Nano constraints (verified — don't step on these again)

- Dependencies are `dev-master` of `swoole/typephp`, `swoole/php-nano` and `swoole/phpx`, pinned in `composer.lock`. With the v0.8.1 / php-nano 1.0.1 releases the `standard` module was not registered in Nano builds.
- **No enums** — reading an enum case crashes the binary (`zend_enum_get_case`). Use a `final class` with `int` constants.
- **`int / int` is integer division** (as in C++). Write `1000.0 / $x` for a float, or `intdiv()` for an explicit integer.
- Inside a namespace, prefix global constants with `\`: `\PHP_INT_MAX`.
- Native code must not import `poll`, `select`, `ioctl`, processes, sockets, signals or `mmap` — the Nano audit fails the Linux build. The macOS audit misses these, so check Linux (CI or a container) after touching `runtimes/typephp/native/`.
- Don't pass properties by reference to functions (`array_shift($this->x)`, `sort($this->x)`, …) when the property is assigned again later: TypePHP leaves it as a PHP reference and the next assignment segfaults the binary. Work on a copy or use `array_slice()`. PHPUnit can't catch this — only the binary crashes, so run `make smoke`.
- Don't use SPL exceptions (`RuntimeException` etc.) — they break the Nano link step; use `Exception` only.
- No `ctype_*`, `mb_*`, `getenv` (compile error), `flush` (fails at runtime). No processes, signals, sockets or `include/require`.
- In hot loops, cast array elements to their type: `(int) $grid[$i]` — otherwise the variable becomes `php::Var`. Check the result in `build/src/*.cc`.
- `#[\Native]` classes are not usable yet: `$this->method($arg)` does not compile, native objects can't be passed to regular classes or interfaces, and static methods and array storage are not allowed.
- `ArrayAccess` + `??=` and `count()` with spreads in arrays are known compiler bugs — avoid them.

## Style

- PHP 8.4+: `final`/`readonly` classes, promoted properties, `private(set)`, typed constants, `match`.
- PHPStan level max and PER-CS must stay clean — fix the cause instead of adding ignores or baselines. Array element ranges go in PHPDoc (`list<int<0, 255>>`); TypePHP ignores PHPDoc.
- Code must behave the same on every runtime — run `make test`, `make analyse`, `make cs` and `make parity` after changes.
- Never change `src/` just to please an experimental runtime (phpr, elephc): work around it in its `runtimes/` folder or record the limit in its README. Workarounds that are harmless everywhere may go into `src/`.
