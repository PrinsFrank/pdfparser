<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

/** A 2D vector in page (device) space. */
readonly class Vector {
    public function __construct(
        public float $x,
        public float $y,
    ) {}

    /** Euclidean length of the vector. */
    public function length(): float {
        return hypot($this->x, $this->y);
    }

    /** The unit vector in the same direction, or the zero vector when there is no direction to preserve. */
    public function normalized(): self {
        $length = $this->length();
        if ($length === 0.0) {
            return new self(0.0, 0.0);
        }

        return new self($this->x / $length, $this->y / $length);
    }

    /** Dot (scalar) product with $other; for unit vectors this is the cosine of the angle between them. */
    public function dot(self $other): float {
        return $this->x * $other->x + $this->y * $other->y;
    }

    /** This vector rotated 90 degrees counter-clockwise: (x, y) -> (-y, x). */
    public function normal(): self {
        return new self(-$this->y, $this->x);
    }
}
