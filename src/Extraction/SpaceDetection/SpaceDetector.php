<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\SpaceDetection;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

class SpaceDetector {
    public static function shouldContainExtraSpace(
        PositionedTextElement $positionedTextElement,
        PositionedTextElement $previousTextElementOnLine,
    ): bool {
        $gap = $positionedTextElement->absoluteMatrix->offsetX
            - $previousTextElementOnLine->absoluteMatrix->offsetX
            - $previousTextElementOnLine->getAdvanceWidth();

        $wordBreakThreshold = $previousTextElementOnLine->textState->getFontSize()
            * $previousTextElementOnLine->absoluteMatrix->scaleX
            * ($previousTextElementOnLine->textState->scale / 100)
            * PositionedTextElement::WORD_BREAK_THRESHOLD_EM;

        return $gap >= $wordBreakThreshold;
    }
}
