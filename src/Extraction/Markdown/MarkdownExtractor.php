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

        $lineNodes = [];
        foreach ($lineGroupedElements as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $lineNodes[] = new Text("\n");
            }

            $previousTextElementOnLine = null;
            $previousTextElementOnLineText = '';
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                $elementText = $positionedTextElement->getText($page);
                if ($elementText === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    continue;
                }

                $insertSpace = false;
                $font = $positionedTextElement->getFont($page);
                if ($previousTextElementOnLine !== null) {
                    $gap = $positionedTextElement->absoluteMatrix->offsetX
                        - $previousTextElementOnLine->absoluteMatrix->offsetX
                        - $previousTextElementOnLine->getAdvanceWidth($font);

                    $wordBreakThreshold = $previousTextElementOnLine->textState->getFontSize()
                        * $previousTextElementOnLine->absoluteMatrix->scaleX
                        * ($previousTextElementOnLine->textState->scale / 100)
                        * PositionedTextElement::WORD_BREAK_THRESHOLD_EM;

                    if (
                        $gap >= $wordBreakThreshold
                        && str_ends_with($previousTextElementOnLineText, ' ') === false
                        && str_starts_with($elementText, ' ') === false
                    ) {
                        $insertSpace = true;
                    }
                }

                $lineNodes[] = new Text(($insertSpace ? ' ' : '') . $elementText);
                $previousTextElementOnLine = $positionedTextElement;
                $previousTextElementOnLineText = $elementText;
            }
        }

        return new Document(new Paragraph(...$lineNodes));
    }
}
