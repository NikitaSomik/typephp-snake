#!/usr/bin/env bash
# Runs both benchmark workloads on every runtime available on this machine and
# prints one Markdown table. Usage (from the repository root, or `make bench`):
#
#   bench/run.sh                    all runtimes found on this machine
#   bench/run.sh php typephp        only these
#   RUNS=5 GAMES=200 FIB=32 bench/run.sh
#   SAVE=1 bench/run.sh             also write bench/results/<host>-<date>.md
#
# Each workload runs RUNS times and the table shows the median. The checksum
# column compares every runtime with the php reference: a runtime that played
# different games is marked MISMATCH and its speed means nothing.
set -euo pipefail
cd "$(dirname "$0")/.."
. bench/runtimes.sh

RUNS=${RUNS:-3}
GAMES=${GAMES:-200}
FIB=${FIB:-32}
runtimes=${*:-$ALL_RUNTIMES}

median() { sort -g | awk '{ v[NR] = $1 } END { if (NR) print v[int((NR + 1) / 2)] }'; }
field() { awk -v pat="$1" -v col="$2" '$0 ~ pat { n = split($0, f, /[ \t]+/); print f[col < 0 ? n + col + 1 : col] }'; }

reference_game=""
reference_fib=""
rows=""

for rt in $runtimes; do
    cmd=$(runtime_cmd "$rt") || true
    if [ -z "$cmd" ]; then
        echo "skip $rt: $(runtime_hint "$rt")" >&2
        continue
    fi
    echo "== $rt: $cmd" >&2

    rules="" render="" fib="" game_sum="" fib_sum="" failed=""
    for _ in $(seq "$RUNS"); do
        if ! out=$($cmd --bench="$GAMES" 2>&1); then failed="game"; echo "$out" >&2; break; fi
        rules+="$(echo "$out" | field '^rules only:' -2)"$'\n'
        render+="$(echo "$out" | field '^rules \\+ render:' -2)"$'\n'
        game_sum=$(echo "$out" | grep '^checksum:' || true)

        if ! out=$($cmd --bench-fib="$FIB" 2>&1); then failed="fib"; echo "$out" >&2; break; fi
        fib+="$(echo "$out" | field '^fib:' 2)"$'\n'
        fib_sum=$(echo "$out" | grep '^checksum:' || true)
    done

    if [ "$rt" = php ]; then
        reference_game=$game_sum
        reference_fib=$fib_sum
        check="reference"
    elif [ -n "$failed" ]; then
        check="FAILED ($failed)"
    elif [ -z "$reference_game" ]; then
        check="not compared"
    elif [ "$game_sum" = "$reference_game" ] && [ "$fib_sum" = "$reference_fib" ]; then
        check="ok"
    else
        check="MISMATCH"
    fi

    r=$(printf '%s' "$rules" | median)
    d=$(printf '%s' "$render" | median)
    f=$(printf '%s' "$fib" | median)
    rows+=$(printf '| %s | %s | %s | %s | %s | %s |' "$rt" "$(runtime_version "$rt")" \
        "$( [ -n "$r" ] && awk -v v="$r" 'BEGIN { f = v < 10000 ? "%.1f k" : "%.0f k"; printf f, v / 1000 }' )" \
        "$( [ -n "$d" ] && awk -v v="$d" 'BEGIN { f = v < 10000 ? "%.1f k" : "%.0f k"; printf f, v / 1000 }' )" \
        "$( [ -n "$f" ] && echo "$f s" )" "$check")$'\n'
done

table="| Runtime | Version | Rules only, ticks/s | Rules + render, ticks/s | fib($FIB), time | Checksum |
|---|---|---:|---:|---:|---|
$rows"

echo
echo "$GAMES autopilot games and fib($FIB), median of $RUNS runs, $(uname -sm)"
echo
printf '%s' "$table"

if [ "${SAVE:-}" = 1 ]; then
    file="bench/results/$(hostname -s)-$(date +%F).md"
    {
        echo "# Benchmark on $(hostname -s), $(date +%F)"
        echo
        echo "$GAMES autopilot games and fib($FIB), median of $RUNS runs, $(uname -sm)."
        echo
        printf '%s' "$table"
    } > "$file"
    echo
    echo "saved to $file"
fi
