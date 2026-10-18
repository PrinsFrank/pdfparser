<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping\ColumnGroupingStrategy;
use PrinsFrank\PdfParser\Extraction\TextGrouping\LineGrouping\TextOverlapStrategy;

class TextGrouping {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @param array<string, Font> $fontCache
     * @return list<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements, Page $page, array &$fontCache): array {
        $elementsGroupedByLine = [];
        foreach (ColumnGroupingStrategy::group($positionedTextElements, $page, $fontCache) as $elementsInColumn) {
            foreach (TextOverlapStrategy::group($elementsInColumn) as $elementsOnLineInColumn) {
                $elementsGroupedByLine[] = $elementsOnLineInColumn;
            }
        }

        return $elementsGroupedByLine;
    }
}
