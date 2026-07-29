<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown;

use PrinsFrank\MarkDownDom\Document;
use PrinsFrank\MarkDownDom\Node\Block\Paragraph;
use PrinsFrank\MarkDownDom\Node\Inline\Text;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\Markdown\TextGrouping\LineGrouping\TextOverlapStrategy;

class MarkdownExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractContent(array $positionedTextElements, Page $page): Document {
        $lineGroupedElements = TextOverlapStrategy::group($positionedTextElements);

        $text = '';
        foreach ($lineGroupedElements as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $text .= "\n";
            }

            $previousTextElementOnLine = null;
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

        return new Document(new Paragraph(new Text($text)));
    }
}
