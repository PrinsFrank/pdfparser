<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Parsing;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

/** @deprecated */
class RollingCharBuffer {
    /** @var int<1, max> $length */
    private int $length;

    /** @var int<0, max> */
    private int $currentIndex = 0;

    /**
     * Rolling buffer, where the modulo of the index is used. Fe: when writing 'a', 'b', 'c', 'd', 'e', 'f' to a buffer of length 3:
     * ['a']
     * ['a', 'b']
     * ['a', 'b', 'c']
     * ['d', 'b', 'c']
     * ['d', 'e', 'c']
     * ['d', 'e', 'f']
     *
     * @var array<int<0, max>, string>
     */
    private array $buffer = [];

    /** @phpstan-assert int<1, max> $length */
    public function __construct(int $length) {
        if ($length < 1) {
            throw new InvalidArgumentException('A negative or zero buffer length doesn\'t make sense, "' . $length . '" provided');
        }

        $this->length = $length;
    }

    public function next(string $char): self {
        $this->currentIndex++;
        $this->buffer[$this->currentIndex % $this->length] = $char;

        return $this;
    }

    public function getPreviousCharacter(int $nAgo = 1): ?string {
        if ($nAgo >= $this->length) {
            throw new InvalidArgumentException('Buffer length of "' . $this->length . '" configured, but character "-' . $nAgo . '" requested');
        }

        return $this->buffer[($this->currentIndex - $nAgo) % $this->length] ?? null;
    }

    /**
     * @phpstan-assert non-empty-string $string
     */
    public function seenString(string $string): bool {
        $strlen = strlen($string);
        if ($strlen === 0) {
            throw new InvalidArgumentException('Cannot assert if non empty string has been encountered');
        }

        if ($strlen > $this->length) {
            throw new InvalidArgumentException(sprintf('Buffer length of %d configured, but value with length %d requested', $this->length, strlen($string)));
        }

        foreach (str_split($string) as $index => $char) {
            $previousChar = $this->getPreviousCharacter($strlen - $index - 1);
            if ($previousChar !== $char) {
                return false;
            }
        }

        return true;
    }

    public function seenReverseString(string $string): bool {
        if (strlen($string) > $this->length) {
            throw new InvalidArgumentException(sprintf('Buffer length of %d configured, but enum with length %d requested', $this->length, strlen($string)));
        }

        foreach (str_split($string) as $index => $char) {
            $previousChar = $this->getPreviousCharacter($index);
            if ($previousChar !== $char) {
                return false;
            }
        }

        return true;
    }
}
