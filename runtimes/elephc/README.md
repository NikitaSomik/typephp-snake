# elephc (experimental, benchmarks only)

[elephc](https://elephc.dev) is an ahead-of-time compiler written in Rust. It compiles a static subset of PHP straight to native machine code, with no Zend engine at run time. That makes it an interesting contrast to TypePHP, which also compiles ahead of time but keeps the Zend runtime routines for arrays, strings and objects.

```bash
# install: https://elephc.dev/docs/getting-started/installation/
make build-elephc                          # elephc runtimes/elephc/snake.php  →  runtimes/elephc/snake
runtimes/elephc/snake --bench
runtimes/elephc/snake --bench-fib
bench/parity.sh elephc                     # same games and results as php?
```

`snake.php` is the entry point. elephc compiles one file and resolves `require` at compile time, so it requires the game's source files one by one instead of using Composer's autoloader. The binary is written next to it as `runtimes/elephc/snake` (ignored by git).

## Status

- **Benchmarks:** the goal of this first step. CI builds the binary on Linux and macOS and compares its checksums with php (`continue-on-error`, so a failure does not block a pull request).
- **Interactive play:** not ported yet. `terminal.php` is a stub, so the game exits with a message. elephc lists `shell_exec`, `stream_select` and `ob_start` as supported, so a port of `runtimes/php/terminal.php` or a small C file through elephc's FFI are both options.

## Known limits

Nothing verified yet. Record each finding here with the elephc version it was reproduced on.
