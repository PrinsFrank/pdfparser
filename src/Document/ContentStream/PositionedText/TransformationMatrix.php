<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

readonly class TransformationMatrix {
    public function __construct(
        public float $scaleX,  // a
        public float $shearX,  // b
        public float $shearY,  // c
        public float $scaleY,  // d
        public float $offsetX, // e
        public float $offsetY, // f
    ) {}

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
