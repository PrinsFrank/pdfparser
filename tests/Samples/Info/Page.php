<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

class Page {
    /** @param list<string> $imagePaths */
    public function __construct(
        public readonly string $content,
        public readonly array $imagePaths,
    ) {
    }
}
