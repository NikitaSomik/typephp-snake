<?php

namespace Snake;

/**
 * Builds a whole frame as one string of ANSI escape codes, so every frame is
 * written to the terminal with a single echo + flush and never flickers.
 * Each board cell is two columns wide to look roughly square.
 */
final class Renderer
{
    private const string RESET = "\e[0m";
    private const string WALL_COLOR = "\e[34m";
    private const string HEAD_COLOR = "\e[1;92m";
    private const string BODY_COLOR = "\e[32m";
    private const string FOOD_COLOR = "\e[1;91m";
    private const string EMPTY_COLOR = "\e[90m";
    private const string TEXT_COLOR = "\e[0m";
    private const string ACCENT_COLOR = "\e[1;93m";

    private const int HEADER_LINES = 2;
    private const int FOOTER_LINES = 1;

    private const int EMPTY = 0;
    private const int HEAD = 1;
    private const int BODY = 2;
    private const int FOOD = 3;
    private const int TEXT_COLS = 52;

    private string $wall;

    /** @var list<string> glyph per cell kind, indexed by EMPTY/HEAD/BODY/FOOD */
    private array $glyphs;

    /** @var list<string> color per cell kind */
    private array $colors = [self::EMPTY_COLOR, self::HEAD_COLOR, self::BODY_COLOR, self::FOOD_COLOR];

    /** @var array<int, string> rendered footer lines by state and width */
    private array $footers = [];

    private int $lastCols = -1;
    private int $lastRows = -1;

    public function __construct(bool $ascii)
    {
        $this->wall = $ascii ? '##' : '██';
        $this->glyphs = $ascii ? ['  ', '@@', 'oo', '**'] : [' ·', '██', '▓▓', '◀▶'];
    }

    private function requiredCols(int $boardWidth): int
    {
        return max(($boardWidth + 2) * 2, self::TEXT_COLS);
    }

    private function requiredRows(int $boardHeight): int
    {
        return $boardHeight + 2 + self::HEADER_LINES + self::FOOTER_LINES;
    }

    public function fits(Game $game, int $cols, int $rows): bool
    {
        return $cols >= $this->requiredCols($game->width) && $rows >= $this->requiredRows($game->height);
    }

    public function render(Game $game, int $best, int $cols, int $rows): string
    {
        $out = "\e[H";
        if ($cols !== $this->lastCols || $rows !== $this->lastRows) {
            // The window was resized: wipe leftovers of the previous layout.
            $out .= "\e[2J";
            $this->lastCols = $cols;
            $this->lastRows = $rows;
        }

        if (!$this->fits($game, $cols, $rows)) {
            return $out . $this->tooSmall($game, $cols, $rows);
        }

        // Text lines are centered as a block; a narrow board is centered on its own.
        $boardCols = ($game->width + 2) * 2;
        $pad = str_repeat(' ', intdiv($cols - $boardCols, 2));
        $textPad = str_repeat(' ', intdiv($cols - $this->requiredCols($game->width), 2));
        $topPad = intdiv($rows - $this->requiredRows($game->height), 2);

        $lines = [];
        for ($i = 0; $i < $topPad; $i++) {
            $lines[] = '';
        }
        $lines[] = $textPad . self::ACCENT_COLOR . 'S N A K E' . self::RESET
            . self::EMPTY_COLOR . '  PHP → C++ → native, compiled by TypePHP' . self::RESET;
        $lines[] = $textPad . $this->statusLine($game, $best);

        $wallRow = $pad . self::WALL_COLOR . str_repeat($this->wall, $game->width + 2) . self::RESET;
        $lines[] = $wallRow;
        foreach ($this->boardRows($game) as $row) {
            $lines[] = $pad . self::WALL_COLOR . $this->wall . $row . self::WALL_COLOR . $this->wall . self::RESET;
        }
        $lines[] = $wallRow;
        $lines[] = $textPad . $this->footer($game);

        return $out . implode("\e[K\n", $lines) . "\e[K\e[J";
    }

    /** @return list<string> */
    private function boardRows(Game $game): array
    {
        $width = $game->width;
        $height = $game->height;

        // Flat grid of cell kinds, row by row.
        $grid = array_fill(0, $width * $height, self::EMPTY);
        $segmentKind = self::HEAD;
        foreach ($game->snake->cells() as $key) {
            $cell = (int) $key;
            $grid[($cell >> 16) * $width + ($cell & 0xFFFF)] = $segmentKind;
            $segmentKind = self::BODY;
        }
        if ($game->food !== null) {
            $grid[$game->food->y * $width + $game->food->x] = self::FOOD;
        }

        // Hot loop: work on locals, not properties, and compare ints, not strings.
        // The (int) cast is a no-op for PHP, but tells TypePHP the element type,
        // so $kind becomes a native int64_t instead of a dynamic zval.
        $glyphs = $this->glyphs;
        $colors = $this->colors;
        $rows = [];
        for ($y = 0; $y < $height; $y++) {
            $row = '';
            $x = 0;
            $offset = $y * $width;
            while ($x < $width) {
                // Run-length: one color code + str_repeat per run of equal cells
                // instead of one concatenation per cell.
                $kind = (int) $grid[$offset + $x];
                $run = 1;
                while ($x + $run < $width && (int) $grid[$offset + $x + $run] === $kind) {
                    $run++;
                }
                $row .= $colors[$kind] . str_repeat($glyphs[$kind], $run);
                $x += $run;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function statusLine(Game $game, int $best): string
    {
        // 1000.0, not 1000: TypePHP compiles int / int to C++ integer division.
        $speed = 1000.0 / $game->tickIntervalMs();

        return self::TEXT_COLOR . 'Score ' . self::ACCENT_COLOR . str_pad((string) $game->score, 5)
            . self::TEXT_COLOR . ' Length ' . self::ACCENT_COLOR . str_pad((string) $game->snake->length(), 4)
            . self::TEXT_COLOR . ' Speed ' . self::ACCENT_COLOR . sprintf('%4.1f/s', $speed)
            . self::TEXT_COLOR . '  Best ' . self::ACCENT_COLOR . $best . self::RESET;
    }

    /** Controls hint while playing, state message otherwise; centered once and cached. */
    private function footer(Game $game): string
    {
        $cols = $this->requiredCols($game->width);
        $cacheKey = $game->state * 10000 + $cols;
        if (isset($this->footers[$cacheKey])) {
            return $this->footers[$cacheKey];
        }

        $message = match ($game->state) {
            Game::PAUSED => 'PAUSED · P to continue',
            Game::OVER => 'GAME OVER · R restart · Q quit',
            Game::WON => 'YOU WIN! · R restart · Q quit',
            default => '',
        };
        $text = $message === '' ? 'arrows / WASD / hjkl · P pause · Q quit' : $message;
        $length = $this->displayWidth($text);
        $centered = $length >= $cols ? $text : str_repeat(' ', intdiv($cols - $length, 2)) . $text;
        $footer = ($message === '' ? self::EMPTY_COLOR : self::ACCENT_COLOR) . $centered . self::RESET;
        $this->footers[$cacheKey] = $footer;

        return $footer;
    }

    private function tooSmall(Game $game, int $cols, int $rows): string
    {
        return self::ACCENT_COLOR . 'Terminal is too small' . self::RESET . "\e[K\n"
            . sprintf(
                'Need %dx%d, have %dx%d. Enlarge the window or run with --width/--height.',
                $this->requiredCols($game->width),
                $this->requiredRows($game->height),
                $cols,
                $rows,
            ) . "\e[K\e[J";
    }

    /** Counts UTF-8 code points without mbstring, which PHP Nano does not ship. */
    private function displayWidth(string $text): int
    {
        $width = 0;
        $bytes = strlen($text);
        for ($i = 0; $i < $bytes; $i++) {
            if ((ord($text[$i]) & 0xC0) !== 0x80) {
                $width++;
            }
        }

        return $width;
    }
}
