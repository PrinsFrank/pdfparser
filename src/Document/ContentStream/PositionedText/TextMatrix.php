<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

class TextMatrix {
    public function __construct(
        public readonly float $scaleX,  // a
        public readonly float $shearX,  // b
        public readonly float $shearY,  // c
        public readonly float $scaleY,  // d
        public readonly float $offsetX, // e
        public readonly float $offsetY, // f
    ) {
    }
}
