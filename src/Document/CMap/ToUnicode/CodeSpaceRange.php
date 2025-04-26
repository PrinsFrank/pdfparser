<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

class CodeSpaceRange {
    public function __construct(
        public readonly int $codeSpaceStart,
        public readonly int $codeSpaceEnd,
    ) {
    }
}
