PHP ?= php
TPC  = $(PHP) vendor/bin/tpc.php
BIN  = snake

.PHONY: all build test clean

all: build

vendor: composer.json composer.lock
	composer install --no-interaction
	@touch vendor

## Compile PHP → C++ → a standalone native binary (PHP Nano runtime, no libphp).
build: vendor
	$(TPC) project.yml --nano -O2

test: vendor
	$(PHP) vendor/bin/phpunit

clean:
	rm -rf build $(BIN) $(BIN).rsp
