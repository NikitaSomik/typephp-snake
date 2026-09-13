<?php

namespace Snake\Tests;

use PHPUnit\Framework\TestCase;
use Snake\Options;

final class OptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = Options::fromArgv(['snake']);

        self::assertSame(24, $options->width);
        self::assertSame(16, $options->height);
        self::assertFalse($options->ascii);
        self::assertSame('', $options->error);
    }

    public function testParsesAllOptions(): void
    {
        $options = Options::fromArgv(['snake', '--width=40', '--height=20', '--ascii']);

        self::assertSame(40, $options->width);
        self::assertSame(20, $options->height);
        self::assertTrue($options->ascii);
        self::assertSame('', $options->error);
    }

    public function testRejectsInvalidValues(): void
    {
        self::assertStringContainsString('expects a number', Options::fromArgv(['snake', '--width=ten'])->error);
        self::assertStringContainsString('between 8 and 200', Options::fromArgv(['snake', '--height=3'])->error);
        self::assertStringContainsString('Unknown option', Options::fromArgv(['snake', '--fast'])->error);
    }
}
