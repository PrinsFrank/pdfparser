<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown;

use PrinsFrank\MarkDownDom\Contract\BlockNode;
use PrinsFrank\MarkDownDom\Contract\InlineNode;
use PrinsFrank\MarkDownDom\Document;
use PrinsFrank\MarkDownDom\Enum\HeadingLevel;
use PrinsFrank\MarkDownDom\Node\Block\Heading;
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

        $blockNodes = $inLineNodes = [];
        $textBuffer = '';
        $previousElementIsBold = $previousElementIsItalic = false;
        $previousHeadingLevel = null;
        foreach ($lineGroupedElements as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                if ($previousHeadingLevel !== null) {
                    self::flushInLineNodes($inLineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
                    self::flushBlockNodes($blockNodes, $inLineNodes, $previousHeadingLevel);
                    $previousHeadingLevel = null;
                } elseif ($previousElementIsBold || $previousElementIsItalic) {
                    self::flushInLineNodes($inLineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
                    $inLineNodes[] = new Text("\n");
                    $previousElementIsItalic = $previousElementIsBold = false;
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
                $currentHeadingLevel = $font->getHeadingLevel($positionedTextElement->textState, $positionedTextElement->absoluteMatrix);
                if ($previousHeadingLevel !== $currentHeadingLevel) {
                    self::flushInLineNodes($inLineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
                    self::flushBlockNodes($blockNodes, $inLineNodes, $previousHeadingLevel);
                } elseif ($font->isBold() !== $previousElementIsBold || $font->isItalic() !== $previousElementIsItalic) {
                    self::flushInLineNodes($inLineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
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
                $previousHeadingLevel = $currentHeadingLevel;
                $textBuffer .= $elementText;
            }
        }

        self::flushInLineNodes($inLineNodes, $textBuffer, $previousElementIsBold, $previousElementIsItalic);
        self::flushBlockNodes($blockNodes, $inLineNodes, $previousHeadingLevel);

        return new Document(...$blockNodes);
    }

    /**
     * @param list<BlockNode> $blockNodes
     * @param list<InlineNode> $inLineNodes
     */
    private static function flushBlockNodes(array &$blockNodes, array &$inLineNodes, ?HeadingLevel $previousHeadingLevel): void {
        if ($previousHeadingLevel === null) {
            $blockNodes[] = new Paragraph(...$inLineNodes);
        } else {
            $blockNodes[] = new Heading($previousHeadingLevel, ...$inLineNodes);
        }

        $inLineNodes = [];
    }

    /** @param list<InlineNode> $inLineNodes */
    private static function flushInLineNodes(array &$inLineNodes, string &$textBuffer, bool $previousElementIsBold, bool $previousElementIsItalic): void {
        if ($textBuffer === '') {
            return;
        }

        $trailingWhitespace = null;
        if ($previousElementIsBold || $previousElementIsItalic) {
            if (str_starts_with($textBuffer, ' ')) {
                $inLineNodes[] = new Text(substr($textBuffer, 0, strlen($textBuffer) - strlen(ltrim($textBuffer))));
                $textBuffer = ltrim($textBuffer);
            }
            if (str_ends_with($textBuffer, ' ')) {
                $trailingWhitespace = new Text(substr($textBuffer, strlen(rtrim($textBuffer))));
                $textBuffer = rtrim($textBuffer);
            }
        }

        $inLineNode = new Text($textBuffer);
        if ($previousElementIsBold) {
            $inLineNode = new Bold($inLineNode);
        }

        if ($previousElementIsItalic) {
            $inLineNode = new Italic($inLineNode);
        }

        $inLineNodes[] = $inLineNode;
        if ($trailingWhitespace !== null) {
            $inLineNodes[] = $trailingWhitespace;
        }
        $textBuffer = '';
    }
}
