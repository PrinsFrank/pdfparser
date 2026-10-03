<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping\LineGrouping;

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
class TextOverlapStrategy {
    private const OVERLAP_RATIO = .9;

    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return iterable<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements): iterable {
        usort(
            $positionedTextElements,
            fn(PositionedTextElement $a, PositionedTextElement $b): int => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY,
        );

        /** @var array<int, true> $processedIndices */
        $processedIndices = [];
        $nrOfItems = count($positionedTextElements);
        for ($i = 0; $i < $nrOfItems; $i++) {
            if ($processedIndices[$i] ?? false) {
                continue;
            }

            $processedIndices[$i] = true;
            /** @var PositionedTextElement $highestPositionedTextElement */
            $highestPositionedTextElement = $positionedTextElements[$i];
            $positionedTextElementsOnLine = [$highestPositionedTextElement];
            $highestPositionedTextElementHeight = $highestPositionedTextElement->getHeight();
            if ($highestPositionedTextElementHeight === 0.0) {
                yield $positionedTextElementsOnLine;
                continue;
            }

            $highestPositionedTextElementBottom = $highestPositionedTextElement->absoluteMatrix->offsetY;
            $highestElementTop = $highestPositionedTextElementBottom + $highestPositionedTextElementHeight;
            $lineLeftX = $lineRightX = $highestPositionedTextElement->absoluteMatrix->offsetX;
            for ($j = $i + 1; $j < $nrOfItems; $j++) {
                if ($processedIndices[$j] ?? false) {
                    continue;
                }

                $positionedTextElement = $positionedTextElements[$j];
                $currentElementBottom = $positionedTextElement->absoluteMatrix->offsetY;
                $currentHeight = $positionedTextElement->getHeight();
                $currentElementTop = $currentElementBottom + $currentHeight;
                if ($currentElementTop < $highestPositionedTextElementBottom) {
                    break;
                }

                if ($currentHeight === 0.0) {
                    continue;
                }

                $overlap = min($highestElementTop, $currentElementTop) - max($highestPositionedTextElementBottom, $currentElementBottom);
                if ($overlap <= 0.0) {
                    continue;
                }

                $offsetX = $positionedTextElement->absoluteMatrix->offsetX;
                if ($overlap / min($currentHeight, $highestPositionedTextElementHeight) >= self::OVERLAP_RATIO
                    || ($currentHeight < $highestPositionedTextElementHeight && $offsetX >= $lineLeftX && $offsetX <= $lineRightX)) {
                    $positionedTextElementsOnLine[] = $positionedTextElement;
                    $processedIndices[$j] = true;
                    $lineLeftX = min($lineLeftX, $offsetX);
                    $lineRightX = max($lineRightX, $offsetX);
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
