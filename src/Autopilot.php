<?php

namespace Snake;

/**
 * A greedy bot: among the moves that don't crash right away, take the one
 * closest to the food. Not smart, but it plays long, varied games.
 */
final class Autopilot
{
    public function chooseDirection(Game $game): int
    {
        $snake = $game->snake;
        $food = $game->food;
        $best = $snake->direction;
        $bestDistance = \PHP_INT_MAX;

        for ($direction = Direction::UP; $direction <= Direction::LEFT; $direction++) {
            if ($direction === Direction::opposite($snake->direction)) {
                continue;
            }
            $next = $snake->head()->step($direction);
            $eats = $food !== null && $next->equals($food);
            if (!$game->inside($next) || $snake->wouldCollide($next, $eats)) {
                continue;
            }
            $distance = $food === null ? 0 : abs($food->x - $next->x) + abs($food->y - $next->y);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $direction;
            }
        }

        return $best;
    }
}
