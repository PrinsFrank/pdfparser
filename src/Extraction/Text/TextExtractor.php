<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Text;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\Text\TextGrouping\LineGrouping\LineGroupingStrategy;

class TextExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractText(array $positionedTextElements, Document $document, Page $page, LineGroupingStrategy $lineGroupingStrategy): string {
        $text = '';
        foreach ($lineGroupingStrategy->group($positionedTextElements) as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $text .= "\n";
            }

            $previousTextElementOnLine = null;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                $elementText = $positionedTextElement->getText($document, $page);
                if ($elementText === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    continue;
                }

                if ($previousTextElementOnLine !== null) {
                    $gap = $positionedTextElement->absoluteMatrix->offsetX
                        - $previousTextElementOnLine->absoluteMatrix->offsetX
                        - $previousTextElementOnLine->getAdvanceWidth($document, $page);

                    $wordBreakThreshold = $previousTextElementOnLine->textState->getFontSize()
                        * $previousTextElementOnLine->absoluteMatrix->scaleX
                        * ($previousTextElementOnLine->textState->scale / 100)
                        * PositionedTextElement::WORD_BREAK_THRESHOLD_EM;

                    if (
                        $gap >= $wordBreakThreshold
                        && str_ends_with($text, ' ') === false
                        && str_starts_with($elementText, ' ') === false
                    ) {
                        $text .= ' ';
                    }
                }

                $text .= $elementText;
                $previousTextElementOnLine = $positionedTextElement;
            }
        }

        return $text;
    }
}
