<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

class StrictLineGrouping implements LineGroupingStrategy {
    #[Override]
    public function group(array $positionedTextElements): iterable {
        usort(
            $positionedTextElements,
            static function (PositionedTextElement $a, PositionedTextElement $b): int {
                if (($differenceY = $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY) !== 0) {
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
