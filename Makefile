PHP    ?= php
PHPR   ?= phpr
ELEPHC ?= elephc
# elephc's default heap is 8 MB; the game benchmark needs far more. On macOS a
# 1 GB heap stops the binary from loading (dyld cannot map the shared cache).
ifeq ($(shell uname -s),Darwin)
ELEPHC_FLAGS ?= --heap-size=268435456
else
ELEPHC_FLAGS ?= --heap-size=1073741824
endif
TPC     = $(PHP) vendor/bin/tpc.php
BIN     = snake
# Extra game flags, e.g. make play ARGS="--ascii --width=40"
ARGS ?=
# Runtimes for bench and parity, e.g. make bench RUNTIMES="php typephp"
RUNTIMES ?=

export PHP PHPR ELEPHC

.PHONY: all build build-elephc play play-php play-phpr autopilot autopilot-php \
	test analyse cs cs-fix smoke bench parity clean

all: build

vendor: composer.json composer.lock
	composer install --no-interaction
	@touch vendor

## TypePHP: PHP → C++ → a standalone native binary ./snake (PHP Nano runtime, no libphp).
build: vendor
	$(TPC) project.yml --nano -O2

## elephc: PHP → native code without the Zend engine → runtimes/elephc/snake (benchmarks only).
build-elephc:
	$(ELEPHC) $(ELEPHC_FLAGS) runtimes/elephc/snake.php

## Play: native binary (TypePHP) / PHP interpreter / php-rust.
play: build
	./$(BIN) $(ARGS)

play-php: vendor
	$(PHP) runtimes/php/run.php $(ARGS)

play-phpr: vendor
	$(PHPR) runtimes/phpr/run.php $(ARGS)

## Watch the bot play: native binary / PHP interpreter.
autopilot: build
	./$(BIN) --autopilot $(ARGS)

autopilot-php: vendor
	$(PHP) runtimes/php/run.php --autopilot $(ARGS)

test: vendor
	$(PHP) vendor/bin/phpunit

## Static analysis (PHPStan, level max) and code style (PHP-CS-Fixer, PER-CS).
analyse: vendor
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

cs: vendor
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: vendor
	$(PHP) vendor/bin/php-cs-fixer fix

## Play in a pseudo-terminal like a person would: the native binary and the interpreter.
smoke: build
	python3 tests/tty/smoke.py ./$(BIN)
	python3 tests/tty/smoke.py $(PHP) runtimes/php/run.php

## Compare every available runtime on both workloads; SAVE=1 keeps the table in bench/results/.
bench: vendor
	bench/run.sh $(RUNTIMES)

## Every runtime must play identical games and compute identical results.
parity: vendor
	bench/parity.sh $(RUNTIMES)

clean:
	rm -rf build $(BIN) $(BIN).rsp runtimes/elephc/snake
