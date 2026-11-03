# Benchmark on MacBookPro, 2026-11-03

200 autopilot games and fib(32), median of 3 runs, Darwin arm64.

| Runtime | Version | Rules only, ticks/s | Rules + render, ticks/s | fib(32), time | Checksum |
|---|---|---:|---:|---:|---|
| php | 8.5.12 | 201 k | 44 k | 0.2122 s | reference |
| php-jit | 8.5.12 | 370 k | 96 k | 0.0606 s | ok |
| typephp | master@a178223 | 107 k | 34 k | 0.0088 s | ok |
| phpr |  | 18 k | 5 k | 1.1810 s | ok |
