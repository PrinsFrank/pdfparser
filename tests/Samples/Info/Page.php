<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

readonly class Page {
    /** @param list<string> $imagePaths */
    public function __construct(
        public string $content,
        public array  $imagePaths,
    ) {}
}
