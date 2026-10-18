<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

class ColumnGroupingStrategy {
    private const GUTTER = 2;
    private const MIN_COLUMN_WIDTH_PERCENTAGE = .2;
    private const NR_OF_LINES_TO_BE_CONSIDERED_COLUMN = 25;

    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @param array<string, Font> $fontCache
     * @return list<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements, Page $page, array &$fontCache): array {
        if ($positionedTextElements === []) {
            return [];
        }

        usort($positionedTextElements, fn($a, $b) => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY);
        $positionedElementsGroupedByLine = [];
        foreach ($positionedTextElements as $positionedTextElement) {
            if ($positionedElementsGroupedByLine === [] || $positionedElementsGroupedByLine[count($positionedElementsGroupedByLine) - 1][0]->absoluteMatrix->offsetY - $positionedTextElement->absoluteMatrix->offsetY > 3) {
                $positionedElementsGroupedByLine[] = [];
            }
            $positionedElementsGroupedByLine[count($positionedElementsGroupedByLine) - 1][] = $positionedTextElement;
        }

        $columnSplitCandidateX = $lineSegments = [];
        foreach ($positionedElementsGroupedByLine as $lineIndex => $line) {
            usort($line, fn($a, $b) => $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX);
            $prevRight = null;
            $segments = [];
            foreach ($line as $e) {
                $left = $e->absoluteMatrix->offsetX;
                $right = $e->getRight($page, $fontCache);
                $segments[] = [$left, $right];
                if ($prevRight !== null && $left - $prevRight > self::GUTTER) {
                    $columnSplitCandidateX[] = ($prevRight + $left) / 2;
                }
                $prevRight = $prevRight !== null ? max($prevRight, $right) : $right;
            }
            $lineSegments[$lineIndex] = $segments;
        }

        $indexFirstColumnStart = $indexLastColumnEnd = $bestGutter = null;
        $bestNrOfLines = 0;
        foreach (array_unique($columnSplitCandidateX) as $candidateX) {
            $runStart = null;
            foreach ($lineSegments as $i => $segments) {
                if (!self::segmentsCrossX($segments, $candidateX)) {
                    $runStart ??= $i;
                    if ($i - $runStart + 1 > $bestNrOfLines) {
                        $bestNrOfLines = $i - $runStart + 1;
                        $indexFirstColumnStart = $runStart;
                        $indexLastColumnEnd = $i;
                        $bestGutter = $candidateX;
                    }
                } else {
                    $runStart = null;
                }
            }
        }

        if ($indexFirstColumnStart === null || $indexLastColumnEnd === null || $bestGutter === null || $bestNrOfLines < self::NR_OF_LINES_TO_BE_CONSIDERED_COLUMN) {
            return [array_merge(...$positionedElementsGroupedByLine)];
        }

        $positionedTextElementsBody = array_merge(...array_slice($positionedElementsGroupedByLine, $indexFirstColumnStart, $indexLastColumnEnd - $indexFirstColumnStart + 1));
        usort($positionedTextElementsBody, fn($a, $b) => $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX);
        $columns = [];
        $index = 0;
        $columnLeft = $columnRight = null;
        $pageRight = $page->getCropBox()->xBottomRight ?? ($positionedTextElementsBody !== [] ? max(array_map(fn(PositionedTextElement $e) => $e->getRight($page, $fontCache), $positionedTextElementsBody)) : 600);
        $pageLeft = $page->getCropBox()->xTopLeft ?? ($positionedTextElementsBody !== [] ? min(array_map(fn(PositionedTextElement $e) => $e->absoluteMatrix->offsetX, $positionedTextElementsBody)) : 0);
        $minColumnWidth = ($pageRight - $pageLeft) * self::MIN_COLUMN_WIDTH_PERCENTAGE;
        foreach ($positionedTextElementsBody as $positionedTextElementBody) {
            if ($columnRight !== null
                && $columnLeft !== null
                && $positionedTextElementBody->absoluteMatrix->offsetX - $columnRight > self::GUTTER) {
                if ($columnRight - $columnLeft < $minColumnWidth) {
                    return [array_merge(...$positionedElementsGroupedByLine)];
                }

                if ($pageRight - $positionedTextElementBody->absoluteMatrix->offsetX < $minColumnWidth) {
                    $columns[$index][] = $positionedTextElementBody;
                    $columnRight = max($columnRight, $positionedTextElementBody->getRight($page, $fontCache));
                    continue;
                }

                $index++;
                $columnLeft = $positionedTextElementBody->absoluteMatrix->offsetX;
            }
            if ($columnLeft === null) {
                $columnLeft = $positionedTextElementBody->absoluteMatrix->offsetX;
            }
            $columnRight = $columnRight === null ? $positionedTextElementBody->getRight($page, $fontCache) : max($columnRight, $positionedTextElementBody->getRight($page, $fontCache));
            $columns[$index][] = $positionedTextElementBody;
        }

        if ($columnRight !== null && $columnLeft !== null && $columnRight - $columnLeft < $minColumnWidth) {
            return [array_merge(...$positionedElementsGroupedByLine)];
        }

        return array_values(array_filter([
            ($positionedTextElementsHeader = array_slice($positionedElementsGroupedByLine, 0, $indexFirstColumnStart)) === [] ? [] : array_merge(...$positionedTextElementsHeader),
            ...array_values($columns),
            ($positionedTextElementsFooter = array_slice($positionedElementsGroupedByLine, $indexLastColumnEnd + 1)) === [] ? [] : array_merge(...$positionedTextElementsFooter),
        ]));
    }

    /** @param list<array{float, float}> $segments */
    private static function segmentsCrossX(array $segments, float $candidateX): bool {
        foreach ($segments as [$left, $right]) {
            if ($left >= $candidateX) {
                return false;
            }

            if ($right > $candidateX) {
                return true;
            }
        }

        return false;
    }
}
