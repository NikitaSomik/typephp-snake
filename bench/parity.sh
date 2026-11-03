#!/usr/bin/env bash
# Checks that runtimes play identical games and compute identical results:
# each one runs a short game benchmark and fib(25), and its checksum lines must
# match the php reference. Exits 1 on the first difference.
#
#   bench/parity.sh                 every runtime available here except php
#   bench/parity.sh typephp elephc  only these (fails if one is missing)
set -euo pipefail
cd "$(dirname "$0")/.."
. bench/runtimes.sh

explicit=$#
runtimes=${*:-${ALL_RUNTIMES#php }}

checksums() { { $1 --bench=20; $1 --bench-fib=25; } | grep '^checksum:'; }

reference=$(checksums "$(runtime_cmd php)")
echo "php (reference):"
echo "$reference" | sed 's/^/  /'

status=0
for rt in $runtimes; do
    cmd=$(runtime_cmd "$rt") || true
    if [ -z "$cmd" ]; then
        echo "$rt: not available ($(runtime_hint "$rt"))"
        [ "$explicit" -gt 0 ] && status=1
        continue
    fi
    got=$(checksums "$cmd" 2>&1) || true
    if [ "$got" = "$reference" ]; then
        echo "$rt: parity OK"
    else
        echo "$rt: MISMATCH"
        echo "$got" | sed 's/^/  /'
        status=1
    fi
done
exit "$status"
