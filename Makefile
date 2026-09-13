PHP ?= php
TPC  = $(PHP) vendor/bin/tpc.php
BIN  = snake
# Extra game flags, e.g. make play ARGS="--ascii --width=40"
ARGS ?=

.PHONY: all build play play-php autopilot autopilot-php test bench clean

all: build

vendor: composer.json composer.lock
	composer install --no-interaction
	@touch vendor

## Compile PHP → C++ → a standalone native binary (PHP Nano runtime, no libphp).
build: vendor
	$(TPC) project.yml --nano -O2

## Play the native binary.
play: build
	./$(BIN) $(ARGS)

## Play the same code on the regular PHP interpreter.
play-php: vendor
	$(PHP) bin/$(BIN).php $(ARGS)

## Watch the bot play: native binary / PHP interpreter.
autopilot: build
	./$(BIN) --autopilot $(ARGS)

autopilot-php: vendor
	$(PHP) bin/$(BIN).php --autopilot $(ARGS)

test: vendor
	$(PHP) vendor/bin/phpunit

## Headless benchmark: PHP interpreter, PHP + JIT, native binary.
bench: build
	@echo "== PHP $$($(PHP) -r 'echo PHP_VERSION;') interpreter"
	@$(PHP) bin/$(BIN).php --bench
	@echo "== PHP with JIT"
	@$(PHP) -d opcache.enable_cli=1 -d opcache.jit=on -d opcache.jit_buffer_size=64M bin/$(BIN).php --bench
	@echo "== native binary (TypePHP)"
	@./$(BIN) --bench

clean:
	rm -rf build $(BIN) $(BIN).rsp
