<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Text\TextGrouping\Clustering;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

readonly class Cluster {
    /** @param list<PositionedTextElement> $elements */
    public function __construct(
        public array $elements,
        public float $left,
        public float $right,
        public float $bottom,
        public float $top,
    ) { }
}
