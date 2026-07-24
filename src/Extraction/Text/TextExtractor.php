<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Text;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\Text\TextGrouping\Clustering\BoundingBoxClusterer;
use PrinsFrank\PdfParser\Extraction\Text\TextGrouping\LineGrouping\TextOverlapStrategy;

class TextExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractText(array $positionedTextElements, Page $page): string {
        $text = '';
        $previousTextElementOnLine = null;
        $clusters = BoundingBoxClusterer::cluster($positionedTextElements, $page);
        foreach ($clusters as $i => $cluster) {
            if ($i !== 0) {
                $previousCluster = $clusters[$i - 1];
                if (min($previousCluster->top, $cluster->top) <= max($previousCluster->bottom, $cluster->bottom)) {
                    $text .= "\n";
                    $previousTextElementOnLine = null;
                }
            }

            foreach (TextOverlapStrategy::group($cluster->elements) as $j => $positionedTextElementsForLine) {
                if ($j !== 0) {
                    $text .= "\n";
                    $previousTextElementOnLine = null;
                }

                foreach ($positionedTextElementsForLine as $positionedTextElement) {
                    $elementText = $positionedTextElement->getText($page);
                    if ($elementText === '') {
                        $previousTextElementOnLine = $positionedTextElement;
                        continue;
                    }

                    if ($previousTextElementOnLine !== null) {
                        $gap = $positionedTextElement->absoluteMatrix->offsetX
                            - $previousTextElementOnLine->absoluteMatrix->offsetX
                            - $previousTextElementOnLine->getAdvanceWidth($page);

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
        }

        return $text;
    }
}
