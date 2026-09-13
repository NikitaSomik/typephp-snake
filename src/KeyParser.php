<?php

namespace Snake;

/**
 * Turns raw terminal bytes into logical keys.
 *
 * Arrow keys arrive as escape sequences: ESC [ A..D (normal cursor mode) or
 * ESC O A..D (application cursor mode). Letters cover WASD and vim's hjkl.
 */
final class KeyParser
{
    private const int ESC = 27;
    private const int CTRL_C = 3;

    /**
     * @param list<int> $bytes
     * @return list<int> Key::* values
     */
    public function parse(array $bytes): array
    {
        $keys = [];
        $count = count($bytes);
        $i = 0;
        while ($i < $count) {
            $byte = $bytes[$i];
            if ($byte === self::ESC && $i + 2 < $count && ($bytes[$i + 1] === 91 || $bytes[$i + 1] === 79)) {
                $key = $this->arrow($bytes[$i + 2]);
                if ($key >= 0) {
                    $keys[] = $key;
                }
                $i += 3;
                continue;
            }
            $key = $this->plain($byte);
            if ($key >= 0) {
                $keys[] = $key;
            }
            $i++;
        }

        return $keys;
    }

    private function arrow(int $code): int
    {
        return match ($code) {
            65 => Key::UP,
            66 => Key::DOWN,
            67 => Key::RIGHT,
            68 => Key::LEFT,
            default => -1,
        };
    }

    private function plain(int $byte): int
    {
        if ($byte === self::CTRL_C) {
            return Key::QUIT;
        }

        return match (strtolower(chr($byte))) {
            'w', 'k' => Key::UP,
            's', 'j' => Key::DOWN,
            'a', 'h' => Key::LEFT,
            'd', 'l' => Key::RIGHT,
            'p', ' ' => Key::PAUSE,
            'r' => Key::RESTART,
            'q' => Key::QUIT,
            default => -1,
        };
    }
}
