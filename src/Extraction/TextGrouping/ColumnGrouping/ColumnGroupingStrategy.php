<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Extraction\TextGrouping\LineGrouping\TextOverlapStrategy;
use ValueError;

/**
 * Take all the left and right offsets of all positioned text elements. Exclude the leftmost and rightmost as we know
 * there is a "gutter" there. Round them down for left, up for right, and divide by the minimum gutter width to reduce
 * the number of gutter candidates.
 *
 * Iterate over all gutter candidates. Check how many horizontal text lines contain elements that cross the gutter.
 * Keep track of the longest streak of misses per gutter, and on what line index that streak starts. If there is no
 * candidate with a streak of more than the minimum number of lines, return early. Prune consecutive gutter candidates
 * that live in the same gutter. Exit when any of the columns is less than the minimum column width percentage.
 *
 * Reduce the gutter candidates to only those within 2 of the highest line count. Take the last line number of the
 * header, and the first line of the footer if either exists. Group the elements in between those by column, then return
 * the header, column(1)...column(n), footer as groups.
 */
class ColumnGroupingStrategy {
    private const MINIMUM_GUTTER_WIDTH = 10;
    private const MINIMUM_COLUMN_WIDTH_PERCENTAGE = 20;
    private const NR_OF_LINES_TO_BE_CONSIDERED_COLUMNS = 25;

    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @param array<string, Font> $fontCache
     * @return list<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements, Page $page, array &$fontCache): array {
        if ($positionedTextElements === []) {
            return [];
        }

        $pageLeft = min(
            $elementsLeft = array_map(
                static fn(PositionedTextElement $e) => floor($e->absoluteMatrix->offsetX / self::MINIMUM_GUTTER_WIDTH) * self::MINIMUM_GUTTER_WIDTH,
                $positionedTextElements,
            ),
        );
        $pageRight = max(
            $elementsRight = array_map(
                static fn(PositionedTextElement $e) => ceil($e->getRight($page, $fontCache) / self::MINIMUM_GUTTER_WIDTH) * self::MINIMUM_GUTTER_WIDTH,
                $positionedTextElements,
            ),
        );
        $xCandidates = array_diff(array_unique([...$elementsLeft, ...$elementsRight]), [$pageLeft, $pageRight]);
        $positionedElementsGroupedByLine = iterator_to_array(TextOverlapStrategy::group($positionedTextElements), false);
        $nrOfLines = count($positionedElementsGroupedByLine);
        $elementMissCounts = $headerEndLineIndices = [];
        sort($xCandidates);
        foreach ($xCandidates as $xCandidateIndex => $xCandidate) {
            $currentMissCount = $maxStreakCount = 0;
            $currentStreakStart = $maxStreakStart = null;
            foreach ($positionedElementsGroupedByLine as $lineIndex => $positionedTextElementsOnLine) {
                if (self::elementOnLineCrossesX($positionedTextElementsOnLine, $xCandidate, $page, $fontCache)) {
                    $currentMissCount = 0;
                    $currentStreakStart = null;
                } else {
                    $currentStreakStart ??= $lineIndex;
                    $currentMissCount++;
                    if ($currentMissCount > $maxStreakCount) {
                        $maxStreakCount = $currentMissCount;
                        $maxStreakStart = $currentStreakStart;
                    }
                }
            }
            $elementMissCounts[$xCandidateIndex] = $maxStreakCount;
            $headerEndLineIndices[$xCandidateIndex] = $maxStreakStart;
        }

        if ($elementMissCounts === []
            || ($maxMissCount = max($elementMissCounts)) < self::NR_OF_LINES_TO_BE_CONSIDERED_COLUMNS) {
            return [$positionedTextElements];
        }

        $elementMissCounts = array_filter($elementMissCounts, fn(int $missCount) => $missCount >= $maxMissCount - 2);
        $pruneIndices = [];
        arsort($elementMissCounts);
        foreach ($elementMissCounts as $candidateXIndex => $elementMissCount) {
            if (array_key_exists($candidateXIndex + 1, $elementMissCounts) && $elementMissCount === $elementMissCounts[$candidateXIndex + 1]) {
                $pruneIndices[] = $candidateXIndex;
            }
        }

        $elementMissCounts = array_diff_key($elementMissCounts, array_flip($pruneIndices));
        $headerEndLineIndices = array_intersect_key($headerEndLineIndices, $elementMissCounts);
        $xGutters = array_intersect_key($xCandidates, $elementMissCounts);
        $minimumColumnWidth = ($pageRight - $pageLeft) / 100 * self::MINIMUM_COLUMN_WIDTH_PERCENTAGE;
        foreach (($zeroIndexedGutters = array_values($xGutters)) as $xGutterIndex => $xGutter) {
            if ($xGutter < ($zeroIndexedGutters[$xGutterIndex - 1] ?? $pageLeft) + $minimumColumnWidth) {
                return [$positionedTextElements];
            }
        }

        try {
            $footerStartLineIndices = array_combine(
                array_keys($headerEndLineIndices),
                array_map(
                    static fn(?int $headerEndLineIndex, int $elementMissCount): int => ($headerEndLineIndex ?? 0) + $elementMissCount,
                    $headerEndLineIndices,
                    $elementMissCounts,
                ),
            );
        } catch (ValueError) {
            throw new ParseFailureException();
        }
        $indexFirstColumnStart = (int) max(0, ...$headerEndLineIndices);
        $indexLastColumnEnd = min($nrOfLines, ...$footerStartLineIndices);
        $columns = [];
        foreach (array_slice($positionedElementsGroupedByLine, $indexFirstColumnStart, $indexLastColumnEnd - $indexFirstColumnStart) as $positionedElementsLineInColumn) {
            foreach ($positionedElementsLineInColumn as $positionedTextElement) {
                $elementLeft = $positionedTextElement->absoluteMatrix->offsetX;
                $elementRight = $positionedTextElement->getRight($page, $fontCache);
                $elementCenter = ($elementLeft + $elementRight) / 2;
                $columnIndex = count($xGutters);
                foreach (array_values($xGutters) as $xGutterIndex => $xGutter) {
                    if ($elementCenter < $xGutter) {
                        $columnIndex = $xGutterIndex;
                        break;
                    }
                }

                $columns[$columnIndex][] = $positionedTextElement;
            }
        }

        ksort($columns);
        return array_values(array_filter([
            ($positionedTextElementsHeader = array_slice($positionedElementsGroupedByLine, 0, $indexFirstColumnStart)) === []
                ? []
                : array_merge(...$positionedTextElementsHeader),
            ...array_values($columns),
            ($positionedTextElementsFooter = array_slice($positionedElementsGroupedByLine, $indexLastColumnEnd)) === []
                ? []
                : array_merge(...$positionedTextElementsFooter),
        ]));
    }

    /**
     * @param list<PositionedTextElement> $positionedTextElementsOnLine
     * @param array<string, Font> $fontCache
     */
    private static function elementOnLineCrossesX(array $positionedTextElementsOnLine, float $x, Page $page, array &$fontCache): bool {
        foreach ($positionedTextElementsOnLine as $positionedTextElementOnLine) {
            if ($positionedTextElementOnLine->absoluteMatrix->offsetX < $x
                && $positionedTextElementOnLine->getRight($page, $fontCache) > $x) {
                return true;
            }
        }

        return false;
    }
}
