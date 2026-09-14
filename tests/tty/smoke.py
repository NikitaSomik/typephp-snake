#!/usr/bin/env python3
"""Plays Snake in a pseudo-terminal the way a person would and checks the screen.

PHPUnit covers the game rules, but not the game loop, the terminal layer or
the compiled binary. This script starts the game in a real pseudo-terminal,
presses keys and reads the frames it draws.

Usage:
    python3 tests/tty/smoke.py ./snake
    python3 tests/tty/smoke.py php bin/snake.php
"""

import fcntl
import os
import pty
import re
import select
import signal
import struct
import sys
import termios
import time

ROWS, COLS = 30, 80
GAME_ARGS = ["--ascii", "--width=16", "--height=10"]
UP = b"\x1b[A"
ANSI = re.compile(r"\x1b\[[0-9;?]*[A-Za-z]")


class Game:
    def __init__(self, command):
        self.pid, self.fd = pty.fork()
        if self.pid == 0:
            os.execvp(command[0], command + GAME_ARGS)
        fcntl.ioctl(self.fd, termios.TIOCSWINSZ, struct.pack("HHHH", ROWS, COLS, 0, 0))
        self.output = b""
        self.status = None

    def pump(self, seconds):
        deadline = time.monotonic() + seconds
        while time.monotonic() < deadline and self.status is None:
            ready, _, _ = select.select([self.fd], [], [], 0.01)
            if ready:
                try:
                    data = os.read(self.fd, 65536)
                except OSError:
                    data = b""
                if data:
                    # Answer cursor position requests like a real terminal does: the
                    # binary asks for one to learn the window size. The last bytes of
                    # the previous chunk are included in case a request was split.
                    window = self.output[-3:] + data
                    for _ in range(window.count(b"\x1b[6n")):
                        os.write(self.fd, b"\x1b[%d;%dR" % (ROWS, COLS))
                    self.output += data
            self.reap()

    def reap(self):
        if self.status is None:
            pid, status = os.waitpid(self.pid, os.WNOHANG)
            if pid:
                self.status = status

    def alive(self):
        self.reap()
        return self.status is None

    def send(self, keys):
        if self.alive():
            try:
                os.write(self.fd, keys)
            except OSError:
                pass  # the game has just exited; the next check reports how

    def screen(self):
        """The last completely drawn frame (frames start with ESC[H and end with ESC[J)."""
        end = self.output.rfind(b"\x1b[J")
        if end < 0:
            return ""
        start = self.output.rfind(b"\x1b[H", 0, end)
        return ANSI.sub("", self.output[start:end].decode("utf-8", "replace"))

    def wait_for(self, text, timeout):
        deadline = time.monotonic() + timeout
        while time.monotonic() < deadline:
            if text in self.screen():
                return True
            if not self.alive():
                return False
            self.pump(0.05)
        return text in self.screen()

    def head_row(self):
        board = [line for line in self.screen().split("\n") if line.strip().startswith("##")]
        for row, line in enumerate(board):
            if "@@" in line:
                return row
        return -1

    def describe_exit(self):
        if self.status is None:
            return "still running"
        if os.WIFSIGNALED(self.status):
            number = os.WTERMSIG(self.status)
            return "killed by signal %d (%s)" % (number, signal.Signals(number).name)
        return "exit code %d" % os.WEXITSTATUS(self.status)

    def stop(self):
        if self.alive():
            os.kill(self.pid, signal.SIGKILL)
            os.waitpid(self.pid, 0)


def main():
    command = sys.argv[1:]
    if not command:
        print(__doc__.strip())
        return 2

    game = Game(command)
    results = []

    def check(name, passed, details=""):
        results.append(passed)
        print("%s - %s%s" % ("ok" if passed else "not ok", name, "" if passed else ": " + details))

    try:
        check("draws the board", game.wait_for("P pause", 5) and game.head_row() >= 0,
              game.describe_exit())

        start_row = game.head_row()
        game.send(UP)
        game.pump(0.35)
        check("turns the snake", 0 <= game.head_row() < start_row,
              "head row %d -> %d" % (start_row, game.head_row()))

        game.send(b"p")
        paused = game.wait_for("PAUSED", 2)
        row = game.head_row()
        game.pump(0.5)
        check("pauses", paused and game.head_row() == row, game.describe_exit())
        game.send(b"p")
        game.wait_for("P pause", 2)

        # The snake keeps going up into the wall.
        check("shows GAME OVER", game.wait_for("GAME OVER", 5), game.describe_exit())

        # Regression: restarting after a turn used to segfault the TypePHP binary.
        game.send(b"r")
        restarted = game.wait_for("P pause", 3)
        game.pump(0.5)
        check("restarts after a turn", restarted and game.alive(), game.describe_exit())

        game.send(b"q")
        deadline = time.monotonic() + 5
        while game.alive() and time.monotonic() < deadline:
            game.pump(0.05)
        after_exit = ANSI.sub("", game.output.split(b"\x1b[?1049l")[-1].decode("utf-8", "replace"))
        check("quits and restores the terminal",
              game.status is not None and os.WIFEXITED(game.status) and os.WEXITSTATUS(game.status) == 0
              and "Thanks for playing" in after_exit,
              game.describe_exit())
    finally:
        game.stop()

    print("%d/%d checks passed for: %s" % (sum(results), len(results), " ".join(command)))
    return 0 if all(results) else 1


if __name__ == "__main__":
    sys.exit(main())
