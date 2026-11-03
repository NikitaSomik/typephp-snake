# php-rust (experimental)

php-rust (`phpr`) is a PHP interpreter written in Rust. It runs the same PHP files as the regular interpreter; only the terminal layer differs.

```bash
make play-phpr                         # play (PHPR=/path/to/phpr if it is not on PATH)
phpr runtimes/phpr/run.php --bench     # game benchmark
bench/parity.sh phpr                   # same games and results as php?
```

phpr is not installed in CI, so it is checked on a developer machine only.

## Results

On an Apple M1 Pro (php-rust `72f688c3`, 3 November 2026), `bench/parity.sh phpr` passes and `make bench` gives 18 k ticks/s for the rules, 5 k ticks/s with rendering and 1.18 s for fib(32): roughly 6–11× slower than the PHP interpreter. See the [Performance table](../../README.md#performance) for all runtimes.

## Known limits

Found while porting the game. php-rust commit `72f688c3` addresses both upstream; the workarounds stay because they are harmless and older builds still need them.

- **Output is held until the script ends.** Everything written through `echo`, `STDOUT` or `php://stdout` appears only at exit, which freezes an interactive game. `terminal.php` captures output with `ob_start()` and writes it to `/dev/stdout` on every `term_flush()`; `run.php` starts this with `term_output_start()`.
- **`stream_select()` misses buffered input.** It does not see bytes already pulled into the stream's read buffer, so reading one byte at a time loses the rest of an escape sequence (arrow keys). `terminal.php` reads all available bytes at once and queues them.
