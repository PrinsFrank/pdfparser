<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\Vector;

/**
 * A run of text elements sharing a single baseline, ordered along it in reading order, together with the geometry
 * that places it on the page: its baseline direction, a reference origin, its height, its highest point, and its
 * earliest position in the content stream.
 */
readonly class Line {
    /** @param list<PositionedTextElement> $elements ordered along the baseline (reading direction) */
    private function __construct(
        public array $elements,
        public Vector $direction,     // unit baseline (advance) direction shared by every element on the line
        public Vector $reference,     // a reference origin on the page for the whole line
        public float $height,         // the tallest glyph extent on the line
        public float $top,            // the highest point the line reaches on the page (largest offsetY)
        public int $documentPosition, // the earliest content-stream position of any element on the line
    ) {}

    /**
     * Collect $elements into a line: order them along $direction (reading direction) and capture the line's
     * highest point on the page and earliest content-stream position.
     *
     * @param list<PositionedTextElement> $elements
     * @param array<int, int>             $documentOrder spl_object_id() => position in the content stream
     */
    public static function fromElements(array $elements, Vector $direction, Vector $reference, float $height, array $documentOrder): self {
        usort(
            $elements,
            static function (PositionedTextElement $a, PositionedTextElement $b) use ($direction): int {
                $projectionA = $a->absoluteMatrix->offsetX * $direction->x + $a->absoluteMatrix->offsetY * $direction->y;
                $projectionB = $b->absoluteMatrix->offsetX * $direction->x + $b->absoluteMatrix->offsetY * $direction->y;

                return $projectionA <=> $projectionB;
            },
        );

        $top = $reference->y;
        $documentPosition = PHP_INT_MAX;
        foreach ($elements as $element) {
            $top = max($top, $element->absoluteMatrix->offsetY);
            $documentPosition = min($documentPosition, $documentOrder[spl_object_id($element)]);
        }

        return new self($elements, $direction, $reference, $height, $top, $documentPosition);
    }

    /** Signed coordinate of the line along $normal: its position in a stack of parallel lines. */
    public function positionAlong(Vector $normal): float {
        return $this->reference->dot($normal);
    }
}
