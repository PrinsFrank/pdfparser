<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

/**
 *    #
 *   # #
 *  #####
 * #     #  #####  __< Baseline of "A" as being crossed by "Z", so will match depending on overlap percentage
 *             #       #   # __< Top of "Y" is below baseline of "A" so will never be considered
 *            #         ##
 *          #####       #
 *
 * Strategy where we sort all positioned text elements, retrieve the very first text element from the page (highest)
 * And for each text element check if there is significant overlap above a threshold. Continue until all elements are processed
 */
class TextOverlapStrategy implements LineGroupingStrategy {
    /** @param int<0, 100> $overlapPercentage */
    public function __construct(
        private readonly int $overlapPercentage = 90,
    ) {}

    #[Override]
    public function group(array $positionedTextElements): iterable {
        // Order lines top to bottom by descending offsetY. Not abs(offsetY): a page whose MediaBox origin sits
        // above its content (negative lower-left, e.g. [0 -792 612 0]) has all-negative offsetY with the topmost
        // line the least negative, which abs() would reverse.
        usort(
            $positionedTextElements,
            fn(PositionedTextElement $a, PositionedTextElement $b): int => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY,
        );

        /** @var array<int, true> $processedIndices */
        $processedIndices = [];
        $nrOfItems = count($positionedTextElements);
        for ($i = 0; $i < $nrOfItems; $i++) {
            if (isset($processedIndices[$i])) {
                continue;
            }

            /** @var PositionedTextElement $highestPositionedTextElement */
            $highestPositionedTextElement = $positionedTextElements[$i];
            $highestPositionedTextElementBottom = $highestPositionedTextElement->absoluteMatrix->offsetY;
            $highestPositionedTextElementHeight = $highestPositionedTextElement->getHeight();

            $positionedTextElementsOnLine = [$highestPositionedTextElement];
            $processedIndices[$i] = true;
            $lineLeftX = $highestPositionedTextElement->absoluteMatrix->offsetX;
            $lineRightX = $highestPositionedTextElement->absoluteMatrix->offsetX;
            for ($j = $i + 1; $j < $nrOfItems; $j++) {
                if (isset($processedIndices[$j])) {
                    continue;
                }

                $positionedTextElement = $positionedTextElements[$j];
                $positionedTextElementHeight = $positionedTextElement->getHeight();

                $highestElementTop = $highestPositionedTextElementBottom + $highestPositionedTextElementHeight;

                $currentElementBottom = $positionedTextElement->absoluteMatrix->offsetY;
                $currentElementTop = $currentElementBottom + $positionedTextElementHeight;

                $smallestElementHeight = min($positionedTextElementHeight, $highestPositionedTextElementHeight);
                if ($smallestElementHeight === 0.0) {
                    continue;
                }

                $overlap = min($highestElementTop, $currentElementTop) - max($highestPositionedTextElementBottom, $currentElementBottom);
                $belongsOnLine = $overlap / $smallestElementHeight * 100 >= $this->overlapPercentage;
                $isEnclosedSubscript = $overlap > 0.0
                    && $positionedTextElementHeight < $highestPositionedTextElementHeight
                    && $positionedTextElement->absoluteMatrix->offsetX >= $lineLeftX
                    && $positionedTextElement->absoluteMatrix->offsetX <= $lineRightX;

                if ($belongsOnLine || $isEnclosedSubscript) {
                    $positionedTextElementsOnLine[] = $positionedTextElement;
                    $processedIndices[$j] = true;
                    $lineLeftX = min($lineLeftX, $positionedTextElement->absoluteMatrix->offsetX);
                    $lineRightX = max($lineRightX, $positionedTextElement->absoluteMatrix->offsetX);
                }
            }

            usort(
                $positionedTextElementsOnLine,
                static fn(PositionedTextElement $a, PositionedTextElement $b): int => $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX,
            );

            yield $positionedTextElementsOnLine;
        }
    }
}
