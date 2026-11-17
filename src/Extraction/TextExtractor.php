<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\SpaceDetection\SpaceDetector;
use PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping\ColumnGroupingStrategy;

class TextExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractContent(array $positionedTextElements): string {
        $textBuffer = '';
        foreach (ColumnGroupingStrategy::group($positionedTextElements) as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $textBuffer .= "\n";
            }

            $previousTextElementOnLine = null;
            $previousTextElementEndsWithSpace = false;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                $elementText = $positionedTextElement->getText();
                if ($elementText === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    continue;
                }

                if ($previousTextElementEndsWithSpace === false
                    && str_starts_with($elementText, ' ') === false
                    && $previousTextElementOnLine !== null
                    && SpaceDetector::shouldContainExtraSpace($positionedTextElement, $previousTextElementOnLine)) {
                    $textBuffer .= ' ';
                }

                $previousTextElementOnLine = $positionedTextElement;
                $previousTextElementEndsWithSpace = str_ends_with($elementText, ' ');
                $textBuffer .= $elementText;
            }
        }

        return $textBuffer;
    }
}
