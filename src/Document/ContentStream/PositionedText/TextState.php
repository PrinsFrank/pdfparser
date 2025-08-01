<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;

class TextState {
    public function __construct(
        public readonly DictionaryKey|ExtendedDictionaryKey|null $fontName, // Tf
        public readonly ?float $fontSize, // Tfs
        public float $charSpace = 0,      // Tc
        public float $wordSpace = 0,      // Tw
        public float $scale = 100,        // Th
        public float $leading = 0,        // Tl
        public int $render = 0,           // Tmode
        public float $rise = 0,           // Trise
    ) {
    }
}
