<?php

namespace Snake\Tests;

use PHPUnit\Framework\TestCase;
use Snake\Autopilot;
use Snake\Direction;
use Snake\Game;
use Snake\Point;
use Snake\SeededRandom;

final class GameTest extends TestCase
{
    public function testNewGameStartsInTheMiddleMovingRight(): void
    {
        $game = new Game(20, 10, new FixedRandom());

        self::assertSame(Game::RUNNING, $game->state);
        self::assertSame(Game::START_LENGTH, $game->snake->length());
        self::assertEquals(new Point(12, 5), $game->snake->head());
        self::assertSame(Direction::RIGHT, $game->snake->direction);
        self::assertEquals(new Point(0, 0), $game->food, 'FixedRandom(0) picks the first free cell');
    }

    public function testTickMovesTheSnake(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        $game->tick();

        self::assertEquals(new Point(13, 5), $game->snake->head());
        self::assertSame(1, $game->ticks);
    }

    public function testHittingTheWallEndsTheGame(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        for ($i = 0; $i < 7; $i++) {
            $game->tick();
        }
        self::assertSame(Game::RUNNING, $game->state, 'head is on the last column');

        $game->tick();
        self::assertSame(Game::OVER, $game->state);
        self::assertTrue($game->isFinished());
    }

    public function testEatingFoodGrowsAndScoresAndSpeedsUp(): void
    {
        // Head at (12,5); the free cell right in front of it is the (5*20 + 13 - 4)th free cell,
        // because the four body cells (9..12, 5) come earlier in row-major order.
        $game = new Game(20, 10, new FixedRandom(5 * 20 + 13 - 4));
        self::assertEquals(new Point(13, 5), $game->food);
        $interval = $game->tickIntervalMs();

        $game->tick();

        self::assertSame(Game::POINTS_PER_FOOD, $game->score);
        self::assertSame(Game::START_LENGTH + 1, $game->snake->length());
        self::assertLessThan($interval, $game->tickIntervalMs());
        self::assertNotNull($game->food);
        self::assertFalse($game->snake->occupies($game->food), 'new food never spawns on the snake');
    }

    public function testUTurnsAreIgnored(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        $game->turn(Direction::LEFT);
        $game->tick();

        self::assertSame(Direction::RIGHT, $game->snake->direction);
        self::assertSame(Game::RUNNING, $game->state);
    }

    public function testQuickTurnsAreQueuedOnePerTick(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        $game->turn(Direction::UP);
        $game->turn(Direction::LEFT); // would be a U-turn relative to RIGHT, but follows UP
        $game->tick();
        self::assertSame(Direction::UP, $game->snake->direction);

        $game->tick();
        self::assertSame(Direction::LEFT, $game->snake->direction);
        self::assertEquals(new Point(11, 4), $game->snake->head());
    }

    public function testPauseFreezesTheWorld(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        $game->togglePause();
        $game->tick();

        self::assertSame(Game::PAUSED, $game->state);
        self::assertSame(0, $game->ticks);

        $game->togglePause();
        $game->tick();
        self::assertSame(1, $game->ticks);
    }

    public function testRestartResetsEverything(): void
    {
        $game = new Game(20, 10, new FixedRandom());
        for ($i = 0; $i < 20; $i++) {
            $game->tick();
        }
        self::assertSame(Game::OVER, $game->state);

        $game->restart();

        self::assertSame(Game::RUNNING, $game->state);
        self::assertSame(0, $game->score);
        self::assertSame(0, $game->ticks);
        self::assertEquals(new Point(12, 5), $game->snake->head());
    }

    public function testRestartAfterTurningIntoAWall(): void
    {
        // Regression: in the TypePHP binary this sequence segfaulted on restart, because
        // array_shift() on the turn queue property turned it into a PHP reference.
        // The interpreter never crashed; tests/tty/smoke.py checks the binary itself.
        $game = new Game(20, 10, new FixedRandom());
        $game->turn(Direction::UP);
        while (!$game->isFinished()) {
            $game->tick();
        }

        $game->restart();
        $game->turn(Direction::DOWN);
        $game->tick();

        self::assertSame(Game::RUNNING, $game->state);
        self::assertEquals(new Point(12, 6), $game->snake->head());
    }

    public function testFillingTheBoardWinsTheGame(): void
    {
        // An 8x8 board holds 64 cells; the game is won once the last free cell is eaten.
        $game = new Game(8, 8, new SeededRandom(7));
        $autopilot = new Autopilot();
        while (!$game->isFinished()) {
            $game->turn($autopilot->chooseDirection($game));
            $game->tick();
        }

        self::assertContains($game->state, [Game::OVER, Game::WON]);
        if ($game->state === Game::WON) {
            self::assertNull($game->food);
            self::assertSame(64, $game->snake->length());
        }
    }

    public function testSeededGamesAreReproducible(): void
    {
        $first = $this->playAutopilotGame(42);
        $second = $this->playAutopilotGame(42);

        self::assertSame($first, $second);
        self::assertGreaterThan(0, $first[1]);
    }

    /** @return array{int, int} ticks and score */
    private function playAutopilotGame(int $seed): array
    {
        $game = new Game(16, 12, new SeededRandom($seed));
        $autopilot = new Autopilot();
        while (!$game->isFinished() && $game->ticks < 10_000) {
            $game->turn($autopilot->chooseDirection($game));
            $game->tick();
        }

        return [$game->ticks, $game->score];
    }
}
