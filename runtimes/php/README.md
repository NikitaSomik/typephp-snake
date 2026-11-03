# PHP interpreter (reference)

`run.php` loads Composer's autoloader, the terminal layer in `terminal.php` and `main.php`, then calls `main()`.

```bash
make play-php                       # play
php runtimes/php/run.php --bench    # game benchmark
php -d opcache.enable_cli=1 -d opcache.jit=on -d opcache.jit_buffer_size=64M runtimes/php/run.php --bench   # with JIT
```

The terminal layer uses `stty` (through `shell_exec`) for raw mode and the window size, and `stream_select` to wait for keys with a timeout.

This runtime is the reference: PHPUnit, PHPStan and the benchmark checksums are all measured against it. Requires PHP 8.4 or 8.5.
