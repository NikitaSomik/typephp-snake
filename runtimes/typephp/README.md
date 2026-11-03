# TypePHP (primary)

[TypePHP](https://github.com/swoole/typephp) by the Swoole team compiles PHP to C++17, and clang or gcc turns that into a native executable. With `--nano` the binary carries the small PHP Nano runtime and needs no PHP on the target machine.

```bash
make build      # vendor/bin/tpc.php project.yml --nano -O2  →  ./snake
./snake
./snake --bench
```

`project.yml` stays in the repository root and lists `main.php`, `src/` and `runtimes/typephp/native/`. The `native/` folder is the C++ terminal layer: `terminal.cc` implements the functions declared in `terminal.stub.php`, and TypePHP maps the C++ function `php_term_read_byte` to the PHP function `term_read_byte()`.

PHP Nano has no processes or signals, and its link-time audit rejects `poll`, `select` and `ioctl`. So raw mode uses `termios`, keys come from a non-blocking `read()` with short sleeps, and the window size comes from a cursor position report.

## Known limits

Found while building this game (September 2026, pinned `master`). The details and workarounds are in the main [README](../../README.md#notes-from-the-typephp-frontier).

- No enums: reading a case crashes the binary. Use a `final class` with `int` constants.
- `int / int` is integer division, as in C++. Write `1000.0 / $x` for a float.
- Inside a namespace, global constants need a leading `\` (`\PHP_INT_MAX`).
- Passing a property by reference (`array_shift($this->queue)`) and reassigning it later segfaults.
- No SPL exceptions, `ctype_*`, `mb_*`, `getenv`, `flush`, `include`/`require`.
- In hot loops, cast array reads (`(int) $grid[$i]`) so the variable becomes a native `int64_t`.
