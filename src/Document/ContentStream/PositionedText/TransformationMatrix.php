<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

class TransformationMatrix {
    public function __construct(
        public readonly float $scaleX,  // a
        public readonly float $shearX,  // b
        public readonly float $shearY,  // c
        public readonly float $scaleY,  // d
        public readonly float $offsetX, // e
        public readonly float $offsetY, // f
    ) {
    }

    public function getAbsoluteY(): float {
        return $this->offsetY * $this->scaleY;
    }

    public function getAbsoluteX(): float {
        return $this->offsetX * $this->scaleX;
    }

    /** Please note that a concatenated transformation matrix of A B !== B A */
    public function multiplyWith(self $other): self {
        return new self(
            $this->scaleX * $other->scaleX + $this->shearX * $other->shearY,
            $this->scaleX * $other->shearX + $this->shearX * $other->scaleY,
            $this->shearY * $other->scaleX + $this->scaleY * $other->shearY,
            $this->shearY * $other->shearX + $this->scaleY * $other->scaleY,
            $this->offsetX * $other->scaleX + $this->offsetY * $other->shearY + $other->offsetX,
            $this->offsetX * $other->shearX + $this->offsetY * $other->scaleY + $other->offsetY,
        );
    }
}
