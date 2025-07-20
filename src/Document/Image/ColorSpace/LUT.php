<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;

class LUT {
    public function __construct(
        public readonly DecoratedObject $decoratedObject,
    ) {
    }
}
