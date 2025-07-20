<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

interface HasComponents {
    public function getComponents(?LUT $lut): Components;
}
