# elephc on MacBookPro, 2026-11-03

50 autopilot games and fib(32), median of 3 runs, Darwin arm64. Fewer games than the main run because elephc's rendering slows down as games accumulate (see runtimes/elephc/README.md); php is included as the checksum reference.

| Runtime | Version | Rules only, ticks/s | Rules + render, ticks/s | fib(32), time | Checksum |
|---|---|---:|---:|---:|---|
| php | 8.5.12 | 205 k | 44 k | 0.2065 s | reference |
| elephc | elephc 0.27.6 | 57 k | 0 k | 0.4003 s | ok |

The render column was printed before bench/run.sh showed values under 10 k with a decimal; each elephc run took about 210 s for roughly 22,000 ticks, about 0.1 k ticks/s.

Single runs of `runtimes/elephc/snake --bench=N`:

| Games | Rules only, ticks/s | Rules + render |
|---:|---:|---|
| 10 | 56,176 | 8.9 s (564 ticks/s) |
| 20 | 58,845 | 26.2 s (347 ticks/s) |
| 40 | 52,259 | 148.2 s (145 ticks/s) |
