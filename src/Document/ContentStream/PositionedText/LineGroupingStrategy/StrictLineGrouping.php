<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

/** Line grouping is done on _exact_ offsetY */
class StrictLineGrouping implements LineGroupingStrategy {
    #[Override]
    public function group(array $positionedTextElements): iterable {
        usort(
            $positionedTextElements,
            static function (PositionedTextElement $a, PositionedTextElement $b): int {
                if (($differenceY = abs($b->absoluteMatrix->offsetY) <=> abs($a->absoluteMatrix->offsetY)) !== 0) {
                    return $differenceY;
                }

                return $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX;
            },
        );

        $previousPositionedTextElement = null;
        $positionedTextElementsInCurrentLine = [];
        foreach ($positionedTextElements as $positionedTextElement) {
            if ($previousPositionedTextElement !== null && $previousPositionedTextElement->absoluteMatrix->offsetY !== $positionedTextElement->absoluteMatrix->offsetY) {
                yield $positionedTextElementsInCurrentLine;
                $positionedTextElementsInCurrentLine = [];
            }

            $positionedTextElementsInCurrentLine[] = $positionedTextElement;
            $previousPositionedTextElement = $positionedTextElement;
        }

        if ($positionedTextElementsInCurrentLine !== []) {
            yield $positionedTextElementsInCurrentLine;
        }
    }
}
