<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class ColumnGroupingStrategy {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return list<list<PositionedTextElement>>
     */
    public static function group(array $positionedTextElements, Page $page): array {
        if ($positionedTextElements === []) {
            return [];
        }

        usort(
            $positionedTextElements,
            fn(PositionedTextElement $a, PositionedTextElement $b): int => $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX,
        );

        $columnIndex = 0;
        $columns = $fontCache = [];

        $font = $fontCache[$positionedTextElements[0]->textState->fontName->value ?? ''] = $page->getFont($positionedTextElements[0]->textState->fontName ?? throw new ParseFailureException('Unable to locate font'))
            ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $positionedTextElements[0]->textState->fontName->value));
        $currentColumnLeft = $positionedTextElements[0]->absoluteMatrix->offsetX;
        $currentColumnRight = $positionedTextElements[0]->absoluteMatrix->offsetX + $positionedTextElements[0]->getAdvanceWidth($font);
        foreach ($positionedTextElements as $positionedTextElement) {
            $font = $fontCache[$positionedTextElement->textState->fontName->value ?? ''] ??= $page->getFont($positionedTextElement->textState->fontName ?? throw new ParseFailureException('Unable to locate font'))
                ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $positionedTextElement->textState->fontName->value));
            $left = $positionedTextElement->absoluteMatrix->offsetX;
            $right = $positionedTextElement->absoluteMatrix->offsetX + $positionedTextElement->getAdvanceWidth($font);
            if ($left - $currentColumnRight > 5) {
                $currentColumnLeft = $left;
                $currentColumnRight = $right;
                $columnIndex++;
            } else {
                $currentColumnLeft = min($currentColumnLeft, $left);
                $currentColumnRight = max($currentColumnRight, $right);
            }

            $columns[$columnIndex][] = $positionedTextElement;
        }

        return array_values($columns);
    }
}
