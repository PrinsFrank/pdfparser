<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown;

use PrinsFrank\MarkDownDom\Document;
use PrinsFrank\MarkDownDom\Node\Block\Paragraph;
use PrinsFrank\MarkDownDom\Node\Inline\Bold;
use PrinsFrank\MarkDownDom\Node\Inline\Italic;
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
        $textBuffer = '';
        $previousElementIsBold = $previousElementIsItalic = false;
        foreach ($lineGroupedElements as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                if ($previousElementIsBold || $previousElementIsItalic) {
                    self::flushNodes($lineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
                    $lineNodes[] = new Text("\n");
                } else {
                    $textBuffer .= "\n";
                }
            }

            $previousTextElementOnLine = null;
            $previousTextElementEndsWithSpace = false;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                $elementText = $positionedTextElement->getText($page);
                if ($elementText === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    continue;
                }

                $font = $positionedTextElement->getFont($page);
                if ($font->isBold() !== $previousElementIsBold
                    || $font->isItalic() !== $previousElementIsItalic) {
                    self::flushNodes($lineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
                }

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
                        && $previousTextElementEndsWithSpace === false
                        && str_starts_with($elementText, ' ') === false
                    ) {
                        $textBuffer .= ' ';
                    }
                }

                $previousTextElementOnLine = $positionedTextElement;
                $previousTextElementEndsWithSpace = str_ends_with($elementText, ' ');
                $previousElementIsItalic = $font->isItalic();
                $previousElementIsBold = $font->isBold();
                $textBuffer .= $elementText;
            }
        }

        self::flushNodes($lineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);

        return new Document(new Paragraph(...$lineNodes));
    }

    private static function flushNodes(array &$lineNodes, string &$textBuffer, bool $previousElementIsBold, bool $previousElementIsItalic): void {
        if ($textBuffer === '') {
            return;
        }

        $trailingWhitespace = null;
        if ($previousElementIsBold || $previousElementIsItalic) {
            if (str_starts_with($textBuffer, ' ')) {
                $lineNodes[] = new Text(substr($textBuffer, 0, strlen($textBuffer) - strlen(ltrim($textBuffer))));
                $textBuffer = ltrim($textBuffer);
            }
            if (str_ends_with($textBuffer, ' ')) {
                $trailingWhitespace = new Text(substr($textBuffer, strlen(rtrim($textBuffer))));
                $textBuffer = rtrim($textBuffer);
            }
        }

        $lineNode = new Text($textBuffer);
        if ($previousElementIsBold) {
            $lineNode = new Bold($lineNode);
        }

        if ($previousElementIsItalic) {
            $lineNode = new Italic($lineNode);
        }

        $lineNodes[] = $lineNode;
        if ($trailingWhitespace !== null) {
            $lineNodes[] = $trailingWhitespace;
        }
        $textBuffer = '';
    }
}
