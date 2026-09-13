<?php

namespace Snake\Tests;

use PHPUnit\Framework\TestCase;
use Snake\Game;
use Snake\Renderer;

final class RendererTest extends TestCase
{
    public function testAsciiFrameShowsTheBoard(): void
    {
        $game = new Game(10, 8, new FixedRandom());
        $screen = $this->plain((new Renderer(true))->render($game, 0, 60, 20));

        $lines = explode("\n", $screen);
        $board = array_values(array_filter($lines, static fn(string $line): bool => str_contains($line, '##')));

        self::assertCount(8 + 2, $board, 'two wall rows plus one row per board row');
        self::assertStringContainsString('##**', $board[1], 'food in the top-left cell');
        self::assertStringContainsString('oooooo@@', $board[1 + 4], 'moving right: body, then head');
        self::assertStringContainsString('Score 0', $screen);
    }

    public function testStateMessagesReplaceTheControlsHint(): void
    {
        $renderer = new Renderer(true);
        $game = new Game(10, 8, new FixedRandom());
        self::assertStringContainsString('P pause', $this->plain($renderer->render($game, 0, 60, 20)));

        $game->togglePause();
        self::assertStringContainsString('PAUSED', $this->plain($renderer->render($game, 0, 60, 20)));
    }

    public function testTooSmallTerminal(): void
    {
        $game = new Game(40, 30, new FixedRandom());
        $renderer = new Renderer(false);

        self::assertFalse($renderer->fits($game, 80, 24));
        self::assertStringContainsString('too small', $renderer->render($game, 0, 80, 24));
    }

    private function plain(string $frame): string
    {
        return (string) preg_replace('/\e\[[0-9;?]*[A-Za-z]/', '', $frame);
    }
}
