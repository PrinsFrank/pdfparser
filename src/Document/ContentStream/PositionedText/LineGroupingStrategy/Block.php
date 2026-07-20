<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\Vector;

/**
 * A group of parallel {@see Line}s that read as one unit -- a paragraph, a column, or a rotated overlay -- sharing
 * a single baseline normal, with the highest point and earliest content-stream position across its lines computed
 * once so ordering the blocks against each other never rescans them.
 */
readonly class Block {
    public float $top;            // the highest point on the page reached by any line in the block (largest offsetY)
    public int $documentPosition; // the earliest content-stream position of any line in the block

    /** @param list<Line> $lines */
    public function __construct(
        public Vector $normal,
        public array $lines,
    ) {
        $top = null;
        $documentPosition = PHP_INT_MAX;
        foreach ($lines as $line) {
            $top = $top === null ? $line->top : max($top, $line->top);
            $documentPosition = min($documentPosition, $line->documentPosition);
        }

        $this->top = $top ?? 0.0;
        $this->documentPosition = $documentPosition;
    }
}
