<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

readonly class Page {
    /**
     * @param list<string> $imagePaths
     * @param list<Annotation> $annotations
     */
    public function __construct(
        public string $text,
        public string $markdown,
        public array  $imagePaths,
        public array  $annotations,
    ) {}
}
