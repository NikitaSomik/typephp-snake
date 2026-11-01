<?php

namespace Snake\Tests;

use PHPUnit\Framework\TestCase;
use Snake\Autopilot;
use Snake\Direction;
use Snake\Game;

final class AutopilotTest extends TestCase
{
    public function testMovesCloserToTheFood(): void
    {
        // The head is at (12, 5) moving right, the food at (0, 0).
        $game = new Game(20, 10, new FixedRandom());

        self::assertSame(Direction::UP, (new Autopilot())->chooseDirection($game));
    }

    public function testNeverTurnsBackOntoItself(): void
    {
        // The food is straight behind the head, which is at (6, 4) moving right.
        $game = new Game(8, 8, new FixedRandom(3 * 8));

        self::assertNotSame(Direction::LEFT, (new Autopilot())->chooseDirection($game));
    }

    public function testTurnsBeforeTheWall(): void
    {
        // The head reaches (7, 4), the right edge; the food is the last free cell, (7, 7).
        $game = new Game(8, 8, new FixedRandom(\PHP_INT_MAX));
        $game->tick();

        self::assertSame(Direction::DOWN, (new Autopilot())->chooseDirection($game));
    }
}
