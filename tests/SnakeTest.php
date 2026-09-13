<?php

namespace Snake\Tests;

use PHPUnit\Framework\TestCase;
use Snake\Cell;
use Snake\Direction;
use Snake\Point;
use Snake\Snake;

final class SnakeTest extends TestCase
{
    public function testConstructorLaysBodyBehindTheHead(): void
    {
        $snake = new Snake(5, 3, 3, Direction::RIGHT);

        self::assertSame([Cell::key(5, 3), Cell::key(4, 3), Cell::key(3, 3)], $snake->cells());
        self::assertEquals(new Point(5, 3), $snake->head());
        self::assertSame(3, $snake->length());
    }

    public function testAdvanceMovesWithoutGrowing(): void
    {
        $snake = new Snake(5, 3, 3, Direction::RIGHT);
        $snake->advance(false);

        self::assertEquals(new Point(6, 3), $snake->head());
        self::assertSame(3, $snake->length());
        self::assertFalse($snake->occupies(new Point(3, 3)), 'the old tail cell is released');
    }

    public function testAdvanceWithGrowthKeepsTheTail(): void
    {
        $snake = new Snake(5, 3, 3, Direction::RIGHT);
        $snake->advance(true);

        self::assertSame(4, $snake->length());
        self::assertTrue($snake->occupies(new Point(3, 3)));
    }

    public function testMovingIntoTheTailIsSafeUnlessGrowing(): void
    {
        // Curl a 4-cell snake into a 2x2 square: head (2,0), tail (2,1) right below it.
        $snake = new Snake(3, 1, 4, Direction::RIGHT);
        $snake->turn(Direction::UP);
        $snake->advance(false);
        $snake->turn(Direction::LEFT);
        $snake->advance(false);
        $snake->turn(Direction::DOWN);

        $tail = $snake->nextHead();
        self::assertEquals(new Point(2, 1), $tail);
        self::assertFalse($snake->wouldCollide($tail, false), 'the tail moves away in the same tick');
        self::assertTrue($snake->wouldCollide($tail, true), 'a growing snake keeps its tail');
        self::assertTrue($snake->wouldCollide(new Point(3, 1), false), 'any other body cell is fatal');
    }

    public function testDirectionHelpers(): void
    {
        self::assertSame(Direction::DOWN, Direction::opposite(Direction::UP));
        self::assertSame(Direction::RIGHT, Direction::opposite(Direction::LEFT));
        self::assertEquals(new Point(2, 4), (new Point(2, 5))->step(Direction::UP));
    }
}
