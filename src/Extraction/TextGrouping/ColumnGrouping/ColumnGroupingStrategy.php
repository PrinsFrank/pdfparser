<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class ColumnGroupingStrategy {
    private const GUTTER = 2;
    private const MIN_COLUMN_WIDTH_PERCENTAGE = .2;
    private const NR_OF_LINES_TO_BE_CONSIDERED_COLUMN = 25;

    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return list<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements, Page $page): array {
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

        $columnSplitCandidateX = [];
        foreach ($positionedElementsGroupedByLine as $line) {
            usort($line, fn($a, $b) => $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX);
            $prevRight = null;
            foreach ($line as $e) {
                $left = $e->absoluteMatrix->offsetX;
                if ($prevRight !== null && $left - $prevRight > self::GUTTER) {
                    $columnSplitCandidateX[] = ($prevRight + $left) / 2;
                }
                $prevRight = $prevRight !== null ? max($prevRight, self::getRight($e, $page)) : self::getRight($e, $page);
            }
        }

        $indexFirstColumnStart = $indexLastColumnEnd = $bestGutter = null;
        $bestNrOfLines = 0;
        foreach (array_unique($columnSplitCandidateX) as $candidateX) {
            $runStart = null;
            foreach ($positionedElementsGroupedByLine as $i => $positionedTextElementsOnLine) {
                if (array_filter($positionedTextElementsOnLine, static fn(PositionedTextElement $e) => $e->absoluteMatrix->offsetX < $candidateX && self::getRight($e, $page) > $candidateX) === []) {
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
        $pageRight = $page->getCropBox()->xBottomRight ?? ($positionedTextElementsBody !== [] ? max(array_map(fn(PositionedTextElement $e) => self::getRight($e, $page), $positionedTextElementsBody)) : 600);
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
                    $columnRight = max($columnRight, self::getRight($positionedTextElementBody, $page));
                    continue;
                }

                $index++;
                $columnLeft = $positionedTextElementBody->absoluteMatrix->offsetX;
            }
            if ($columnLeft === null) {
                $columnLeft = $positionedTextElementBody->absoluteMatrix->offsetX;
            }
            $columnRight = $columnRight === null ? self::getRight($positionedTextElementBody, $page) : max($columnRight, self::getRight($positionedTextElementBody, $page));
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

    private static function getRight(PositionedTextElement $positionedTextElement, Page $page): float {
        $font = $page->getFont($positionedTextElement->textState->fontName ?? throw new ParseFailureException('Unable to locate font'))
            ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $positionedTextElement->textState->fontName->value));

        return $positionedTextElement->getRight($font);
    }
}
