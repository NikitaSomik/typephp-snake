<?php

namespace Snake\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Snake\Key;
use Snake\KeyParser;

final class KeyParserTest extends TestCase
{
    /** @return iterable<string, array{string, list<int>}> */
    public static function inputs(): iterable
    {
        yield 'arrow up' => ["\e[A", [Key::UP]];
        yield 'arrow down' => ["\e[B", [Key::DOWN]];
        yield 'arrow right' => ["\e[C", [Key::RIGHT]];
        yield 'arrow left' => ["\e[D", [Key::LEFT]];
        yield 'application cursor mode' => ["\eOA", [Key::UP]];
        yield 'wasd, any case' => ['wAsD', [Key::UP, Key::LEFT, Key::DOWN, Key::RIGHT]];
        yield 'vim keys' => ['hjkl', [Key::LEFT, Key::DOWN, Key::UP, Key::RIGHT]];
        yield 'several keys in one read' => ["\e[A\e[Dq", [Key::UP, Key::LEFT, Key::QUIT]];
        yield 'pause, restart' => ['p r', [Key::PAUSE, Key::PAUSE, Key::RESTART]];
        yield 'ctrl+c quits' => ["\x03", [Key::QUIT]];
        yield 'unknown bytes are ignored' => ["x1\e[Z\e", []];
    }

    /** @param list<int> $expected */
    #[DataProvider('inputs')]
    public function testParse(string $input, array $expected): void
    {
        $bytes = array_values(array_map(ord(...), str_split($input)));

        self::assertSame($expected, (new KeyParser())->parse($bytes));
    }
}
