# elephc (experimental, benchmarks only)

[elephc](https://elephc.dev) is an ahead-of-time compiler written in Rust. It compiles a static subset of PHP straight to native machine code, with no Zend engine at run time. That makes it an interesting contrast to TypePHP, which also compiles ahead of time but keeps the Zend runtime routines for arrays, strings and objects.

```bash
# install: https://elephc.dev/docs/getting-started/installation/
make build-elephc                          # elephc runtimes/elephc/snake.php  →  runtimes/elephc/snake
runtimes/elephc/snake --bench
runtimes/elephc/snake --bench-fib
bench/parity.sh elephc                     # same games and results as php?
```

`snake.php` is the entry point. elephc compiles one file and resolves `require` at compile time, so it requires the game's source files one by one instead of using Composer's autoloader, and calls `Snake\Cli::run()` instead of `main()`. The binary is written next to it as `runtimes/elephc/snake` (ignored by git).

## Status

- **Benchmarks:** the goal of this first step. CI builds the binary on Linux and macOS and compares its checksums with php (`continue-on-error`, so a failure does not block a pull request).
- **Interactive play:** not ported yet. `terminal.php` is a stub, so the game exits with a message. elephc lists `shell_exec`, `stream_select` and `ob_start` as supported, so a port of `runtimes/php/terminal.php` or a small C file through elephc's FFI are both options.

## First results

On a shared GitHub Actions runner (Linux x86_64, `--bench=20`, `--bench-fib=25`), elephc plays exactly the same games as PHP (`bench/parity.sh elephc` passes), but it is the slowest runtime here so far:

| | PHP 8.5 interpreter (same runner type) | elephc 0.27.6 |
|---|---:|---:|
| Rules only | ≈360 k ticks/s | 55 k ticks/s |
| Rules + render | ≈63 k ticks/s | 0.3 k ticks/s (29 s for 20 games) |
| fib(25) | ≈0.005 s | 0.016 s |

Rendering builds each frame as one long string, which looks like elephc's weak spot. Because of that, a full `make bench` (200 games, 3 runs) takes about 15 minutes on elephc; `make bench GAMES=20` is enough for a first look. These numbers come from one CI run and are rough; measure on a quiet machine before drawing conclusions.

## Known limits

Found with elephc 0.27.6 (November 2026). Each workaround is harmless for the other runtimes, so it lives in the shared code.

| Finding | Workaround |
|---|---|
| A null check does not narrow a nullable property or variable for the rest of an `&&`: `$this->food !== null && $next->equals($this->food)` is rejected (*expects Snake\Point, got Snake\Point\|null*). | `Point::equals()` accepts `?Point` and returns false for null |
| `strspn()` is not available (*Undefined function*). | `Options::isDigits()`, a plain loop over `ord()` |
| A user function named `main()` clashes with elephc's own entry point (*call to main with 2 args for 0 params*). | `main.php` delegates to `Snake\Cli::run()`, which `snake.php` calls directly |
| The default heap is 8 MB and is not grown: the game benchmark stops with *Fatal error: heap memory exhausted*. A 1 GB heap works on Linux, but on macOS the binary then fails to load (*dyld cache not loaded*). | `make build-elephc` passes `--heap-size` 1 GB on Linux, 256 MB on macOS |
| `unset()` of an element of an array property fails in the backend (*unsupported EIR backend feature: unset target shape*). | `Snake::advance()` unsets on a local copy and assigns it back |
