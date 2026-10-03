<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\SpaceDetection;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;

class SpaceDetector {
    public static function shouldContainExtraSpace(
        PositionedTextElement $positionedTextElement,
        PositionedTextElement $previousTextElementOnLine,
        Font $previousFontOnLine,
    ): bool {
        $gap = $positionedTextElement->absoluteMatrix->offsetX
            - $previousTextElementOnLine->absoluteMatrix->offsetX
            - $previousTextElementOnLine->getAdvanceWidth($previousFontOnLine);

        $wordBreakThreshold = $previousTextElementOnLine->textState->getFontSize()
            * $previousTextElementOnLine->absoluteMatrix->scaleX
            * ($previousTextElementOnLine->textState->scale / 100)
            * PositionedTextElement::WORD_BREAK_THRESHOLD_EM;

        return $gap >= $wordBreakThreshold;
    }
}
