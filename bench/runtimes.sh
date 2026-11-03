# Shared by bench/run.sh and bench/parity.sh: how to start the game on each
# runtime. Sourced, not executed. Override the tools with PHP=…, PHPR=…, ELEPHC=….

PHP=${PHP:-php}
PHPR=${PHPR:-phpr}
ELEPHC=${ELEPHC:-elephc}

# Every runtime, in the order of the result table. php is the reference.
ALL_RUNTIMES="php php-jit typephp phpr elephc"

# runtime_cmd NAME → the command line that starts the game, or nothing when the
# runtime is not available on this machine (not installed or not built yet).
runtime_cmd() {
    case "$1" in
        php) command -v "$PHP" >/dev/null && echo "$PHP runtimes/php/run.php" ;;
        php-jit) command -v "$PHP" >/dev/null &&
            echo "$PHP -d opcache.enable_cli=1 -d opcache.jit=on -d opcache.jit_buffer_size=64M runtimes/php/run.php" ;;
        typephp) [ -x ./snake ] && echo "./snake" ;;
        phpr) command -v "$PHPR" >/dev/null && echo "$PHPR runtimes/phpr/run.php" ;;
        elephc) [ -x runtimes/elephc/snake ] && echo "runtimes/elephc/snake" ;;
        *) echo "unknown runtime: $1" >&2; return 2 ;;
    esac
}

# runtime_version NAME → a short version string for the result table.
runtime_version() {
    case "$1" in
        php | php-jit) "$PHP" -r 'echo PHP_VERSION;' 2>/dev/null ;;
        typephp) sed -n '/"name": "swoole\/typephp"/,/"reference"/s/.*"reference": "\(.\{7\}\).*/master@\1/p' composer.lock 2>/dev/null | head -n 1 ;;
        # phpr has no --version flag, so ask the running interpreter.
        phpr)
            local dir
            dir=$(mktemp -d) &&
                printf '<?php echo PHP_VERSION;' >"$dir/version.php" &&
                "$PHPR" "$dir/version.php" 2>/dev/null
            rm -rf "$dir"
            ;;
        elephc) "$ELEPHC" --version 2>/dev/null | head -n 1 ;;
    esac
}

# The reason a runtime is missing, for the skip message.
runtime_hint() {
    case "$1" in
        php | php-jit) echo "install PHP 8.4+" ;;
        typephp) echo "run make build" ;;
        phpr) echo "install php-rust or set PHPR=/path/to/phpr" ;;
        elephc) echo "install elephc, then run make build-elephc" ;;
    esac
}
