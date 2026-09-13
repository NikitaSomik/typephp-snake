<?php

namespace Snake;

/**
 * Headless run: the autopilot plays many seeded games. Pass one measures the
 * game rules alone; pass two also renders every frame into a string, exactly
 * as a real session would. The checksum line proves that the interpreter and
 * the native binary played identical games and drew identical frames.
 */
final class Benchmark
{
    private const int MAX_TICKS_PER_GAME = 5000;

    public function run(int $games, int $width, int $height): string
    {
        $report = sprintf("Snake benchmark: %d autopilot games on a %dx%d board\n", $games, $width, $height);

        $start = hrtime(true);
        $simulation = $this->play($games, $width, $height, false);
        $report .= $this->line('rules only', $simulation[0], hrtime(true) - $start);

        $start = hrtime(true);
        $rendered = $this->play($games, $width, $height, true);
        $report .= $this->line('rules + render', $rendered[0], hrtime(true) - $start);

        return $report . sprintf(
            "checksum: ticks=%d score=%d frame_bytes=%d\n",
            $rendered[0],
            $rendered[1],
            $rendered[2],
        );
    }

    /** @return array{int, int, int} ticks, total score, rendered bytes */
    private function play(int $games, int $width, int $height, bool $render): array
    {
        $renderer = new Renderer(false);
        $autopilot = new Autopilot();
        $ticks = 0;
        $score = 0;
        $frameBytes = 0;

        for ($seed = 1; $seed <= $games; $seed++) {
            $game = new Game($width, $height, new SeededRandom($seed));
            while (!$game->isFinished() && $game->ticks < self::MAX_TICKS_PER_GAME) {
                $game->turn($autopilot->chooseDirection($game));
                $game->tick();
                if ($render) {
                    $frameBytes += strlen($renderer->render($game, $score, 120, 60));
                }
            }
            $ticks += $game->ticks;
            $score += $game->score;
        }

        return [$ticks, $score, $frameBytes];
    }

    private function line(string $label, int $ticks, int $elapsedNs): string
    {
        $seconds = $elapsedNs / 1e9;

        return sprintf(
            "%-15s %8.3f s  %9.0f ticks/s\n",
            $label . ':',
            $seconds,
            $seconds > 0 ? $ticks / $seconds : 0.0,
        );
    }
}
