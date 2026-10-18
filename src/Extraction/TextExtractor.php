<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\SpaceDetection\SpaceDetector;
use PrinsFrank\PdfParser\Extraction\TextGrouping\TextGrouping;

class TextExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractContent(array $positionedTextElements, Page $page): string {
        $fontCache = [];
        $textBuffer = '';
        foreach (TextGrouping::group($positionedTextElements, $page) as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $textBuffer .= "\n";
            }

            $previousTextElementOnLine = null;
            $previousFontOnLine = null;
            $previousTextElementEndsWithSpace = false;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                if ($positionedTextElement->textState->fontName === null) {
                    throw new ParseFailureException('Unable to locate font');
                }

                $font = $fontCache[$positionedTextElement->textState->fontName->value] ??= $page->getFont($positionedTextElement->textState->fontName);
                $elementText = $positionedTextElement->getText($font);
                if ($elementText === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    $previousFontOnLine = $font;
                    continue;
                }

                if ($previousTextElementEndsWithSpace === false
                    && str_starts_with($elementText, ' ') === false
                    && $previousTextElementOnLine !== null
                    && $previousFontOnLine !== null
                    && SpaceDetector::shouldContainExtraSpace($positionedTextElement, $previousTextElementOnLine, $previousFontOnLine)) {
                    $textBuffer .= ' ';
                }

                $previousTextElementOnLine = $positionedTextElement;
                $previousFontOnLine = $font;
                $previousTextElementEndsWithSpace = str_ends_with($elementText, ' ');
                $textBuffer .= $elementText;
            }
        }

        return $textBuffer;
    }
}
