<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

readonly class CodeSpaceRange {
    public function __construct(
        public int $codeSpaceStart,
        public int $codeSpaceEnd,
    ) {}
}
