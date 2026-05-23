<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\RuntimeException;

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
        private readonly int $overlapPercentage = 70,
    ) {}

    #[Override]
    public function group(array $positionedTextElements): iterable {
        usort(
            $positionedTextElements,
            fn(PositionedTextElement $a, PositionedTextElement $b): int => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY,
        );

        $indexOfItemsToProcess = array_keys($positionedTextElements);
        while ($indexOfItemsToProcess !== []) {
            $highestPositionedTextElementIndex = array_shift($indexOfItemsToProcess);
            /** @var PositionedTextElement $highestPositionedTextElement */
            $highestPositionedTextElement = $positionedTextElements[$highestPositionedTextElementIndex] ?? throw new RuntimeException();
            $positionedTextElementsOnLine = [$highestPositionedTextElement];
            foreach ($indexOfItemsToProcess as $indexOfItemToProcess) {
                $positionedTextElement = $positionedTextElements[$indexOfItemToProcess] ?? throw new RuntimeException();

                $highestElementBottom = $highestPositionedTextElement->absoluteMatrix->offsetY;
                $highestElementTop = $highestElementBottom + $highestPositionedTextElement->getHeight();

                $currentElementBottom = $positionedTextElement->absoluteMatrix->offsetY;
                $currentElementTop = $currentElementBottom + $positionedTextElement->getHeight();

                $overlap = min($highestElementTop, $currentElementTop) - max($highestElementBottom, $currentElementBottom);
                $smallestElementHeight = min($positionedTextElement->getHeight(), $highestPositionedTextElement->getHeight());
                if ($smallestElementHeight !== 0.0 && $overlap / $smallestElementHeight * 100 > $this->overlapPercentage) {
                    $positionedTextElementsOnLine[] = $positionedTextElement;
                    $indexOfItemsToProcess = array_diff($indexOfItemsToProcess, [$indexOfItemToProcess]);
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
