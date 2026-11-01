<?php

namespace Snake;

/**
 * Turns raw terminal bytes into logical keys.
 *
 * Arrow keys arrive as escape sequences: ESC [ A..D (normal cursor mode) or
 * ESC O A..D (application cursor mode). Letters cover WASD and vim's hjkl.
 *
 * A sequence is consumed whole, so its bytes never pass for letters: ESC [ 1 ; 5 A
 * is Ctrl+Up, not the key A, and a late window size report ending in R is not a
 * restart. A sequence cut off by the end of a read is finished by the next one.
 */
final class KeyParser
{
    private const int ESC = 27;
    private const int CTRL_C = 3;
    private const int CSI = 91;
    private const int SS3 = 79;
    private const int INCOMPLETE = -1;

    /** @var list<int<0, 255>> the start of a sequence cut off by the end of a read */
    private array $pending = [];

    /**
     * @param list<int<0, 255>> $bytes
     * @return list<int> Key::* values
     */
    public function parse(array $bytes): array
    {
        $bytes = array_merge($this->pending, $bytes);
        $this->pending = [];

        $keys = [];
        $count = count($bytes);
        $i = 0;
        while ($i < $count) {
            $byte = $bytes[$i];
            if ($byte !== self::ESC) {
                $key = $this->plain($byte);
                if ($key >= 0) {
                    $keys[] = $key;
                }
                $i++;
                continue;
            }

            $end = $this->sequenceEnd($bytes, $i);
            if ($end === self::INCOMPLETE) {
                $this->pending = array_slice($bytes, $i);
                break;
            }
            if ($end > $i) {
                $key = $this->arrow($bytes[$end]);
                if ($key >= 0) {
                    $keys[] = $key;
                }
            }
            $i = $end + 1;
        }

        return $keys;
    }

    /**
     * @param list<int<0, 255>> $bytes
     * @param int<0, max> $start
     * @return int<-1, max> index of the last byte of the sequence starting at $start,
     *                      which is $start itself for a bare ESC, or INCOMPLETE
     */
    private function sequenceEnd(array $bytes, int $start): int
    {
        $count = count($bytes);
        if ($start + 1 >= $count) {
            return self::INCOMPLETE;
        }

        $introducer = $bytes[$start + 1];
        if ($introducer === self::SS3) {
            return $start + 2 < $count ? $start + 2 : self::INCOMPLETE;
        }
        if ($introducer !== self::CSI) {
            return $start;
        }

        // Parameter and intermediate bytes are 0x20..0x3F, the final byte is 0x40..0x7E.
        for ($i = $start + 2; $i < $count; $i++) {
            $byte = $bytes[$i];
            if ($byte < 0x20 || $byte > 0x7E) {
                return $i - 1;
            }
            if ($byte >= 0x40) {
                return $i;
            }
        }

        return self::INCOMPLETE;
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

    /** @param int<0, 255> $byte */
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
