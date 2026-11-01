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
        yield 'an arrow with modifiers stays that arrow' => ["\e[1;5A", [Key::UP]];
        yield 'a window size report is not a restart' => ["\e[24;80R", []];
        yield 'a bare escape does not swallow the next key' => ["\eq", [Key::QUIT]];
        yield 'control bytes abort a sequence' => ["\e[1\x03", [Key::QUIT]];
    }

    /** @param list<int> $expected */
    #[DataProvider('inputs')]
    public function testParse(string $input, array $expected): void
    {
        $bytes = array_map(ord(...), str_split($input));

        self::assertSame($expected, (new KeyParser())->parse($bytes));
    }

    public function testDropsAnEndlessSequence(): void
    {
        $parser = new KeyParser();

        self::assertSame([], $parser->parse(array_map(ord(...), str_split("\e[" . str_repeat('1', 100)))));
        self::assertSame([Key::QUIT], $parser->parse([ord('q')]));
    }

    /** @return iterable<string, array{string, list<int>}> */
    public static function sequences(): iterable
    {
        yield 'arrow' => ["\e[A", [Key::UP]];
        yield 'application cursor mode' => ["\eOD", [Key::LEFT]];
        yield 'arrow with modifiers' => ["\e[1;5B", [Key::DOWN]];
        yield 'window size report' => ["\e[24;80R", []];
    }

    /** @param list<int> $expected */
    #[DataProvider('sequences')]
    public function testSequenceSplitAcrossReads(string $input, array $expected): void
    {
        for ($split = 1; $split < strlen($input); $split++) {
            $parser = new KeyParser();
            $keys = array_merge(
                $parser->parse(array_map(ord(...), str_split(substr($input, 0, $split)))),
                $parser->parse(array_map(ord(...), str_split(substr($input, $split)))),
            );

            self::assertSame($expected, $keys, "split at byte {$split}");
        }
    }
}
