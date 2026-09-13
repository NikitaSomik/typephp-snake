<?php

namespace Snake;

/**
 * The game loop: render → wait for input until the next tick is due → tick.
 *
 * Input is polled with a timeout equal to the time left before the next tick,
 * so the process sleeps instead of spinning and still reacts to keys instantly.
 */
final class App
{
    private const string ENTER_SCREEN = "\e[?1049h\e[?25l\e[2J";
    private const string LEAVE_SCREEN = "\e[0m\e[?25h\e[?1049l";

    private int $best = 0;

    public function __construct(
        private readonly Renderer $renderer,
        private readonly KeyParser $parser,
        private readonly ?Autopilot $autopilot = null,
    ) {
    }

    /** @return int process exit code */
    public function run(Game $game): int
    {
        if (!term_raw_on()) {
            echo "Snake needs an interactive terminal (stdin is not a TTY).\n";
            return 1;
        }

        echo self::ENTER_SCREEN;
        try {
            $this->loop($game);
        } finally {
            echo self::LEAVE_SCREEN;
            term_flush();
            term_raw_off();
        }
        echo "Thanks for playing! Score: {$game->score}, best: {$this->best}\n";

        return 0;
    }

    private function loop(Game $game): void
    {
        $nextTick = $this->nowMs() + $game->tickIntervalMs();
        $dirty = true;

        while (true) {
            $cols = term_cols();
            $rows = term_rows();
            if ($dirty) {
                echo $this->renderer->render($game, $this->best, $cols, $rows);
                term_flush();
                $dirty = false;
            }

            $wait = $nextTick - $this->nowMs();
            $byte = term_read_byte($wait > 0 ? $wait : 0);
            if ($byte >= 0) {
                if (!$this->handleInput($game, $byte)) {
                    return;
                }
                $dirty = true;
            }

            $now = $this->nowMs();
            if ($now >= $nextTick) {
                if (!$this->renderer->fits($game, $cols, $rows) && $game->state === Game::RUNNING) {
                    // Never let the snake run while the board can't be seen.
                    $game->togglePause();
                }
                if ($this->autopilot !== null && $game->state === Game::RUNNING) {
                    $game->turn($this->autopilot->chooseDirection($game));
                }
                $game->tick();
                $this->best = max($this->best, $game->score);
                $nextTick = $now + $game->tickIntervalMs();
                $dirty = true;
            }
        }
    }

    /**
     * @param int<0, 255> $firstByte
     * @return bool false when the player quits
     */
    private function handleInput(Game $game, int $firstByte): bool
    {
        // An arrow key is several bytes; they arrive together, so drain them now.
        $bytes = [$firstByte];
        $byte = term_read_byte(0);
        while ($byte >= 0 && count($bytes) < 32) {
            $bytes[] = $byte;
            $byte = term_read_byte(0);
        }

        foreach ($this->parser->parse($bytes) as $key) {
            if ($key === Key::QUIT) {
                return false;
            }
            if ($key === Key::PAUSE) {
                $game->togglePause();
            } elseif ($key === Key::RESTART && $game->isFinished()) {
                $game->restart();
            } elseif (Key::isDirection($key)) {
                $game->turn($key);
            }
        }

        return true;
    }

    private function nowMs(): int
    {
        return intdiv(hrtime(true), 1_000_000);
    }
}
