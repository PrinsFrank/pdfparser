<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown;

use PrinsFrank\MarkDownDom\Document;
use PrinsFrank\MarkDownDom\Node\Block\Paragraph;
use PrinsFrank\MarkDownDom\Node\Inline\Text;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\Markdown\Table\TableExtractor;
use PrinsFrank\PdfParser\Extraction\Markdown\TextGrouping\LineGrouping\TextOverlapStrategy;

class MarkdownExtractor {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @throws PdfParserException
     */
    public static function extractContent(array $positionedTextElements, Page $page): Document {
        $lineGroupedElements = TextOverlapStrategy::group($positionedTextElements);
        $tables = TableExtractor::extract($lineGroupedElements, $page);

        $text = '';
        $renderedTables = $nodes = [];
        foreach ($lineGroupedElements as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $text .= "\n";
            }

            $previousTextElementOnLine = null;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                foreach ($tables as $table) {
                    if (in_array(spl_object_id($positionedTextElement), $table->positionedElementIds) === false) {
                        continue;
                    }

                    if (in_array(spl_object_id($table), $renderedTables)) {
                        continue 2;
                    }

                    if ($text !== '') {
                        $nodes[] = new Paragraph(new Text($text));
                        $text = '';
                    }

                    $nodes[] = $table->table;
                    $renderedTables[] = spl_object_id($table);

                    continue 2;
                }

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

        if ($text !== '') {
            $nodes[] = new Paragraph(new Text($text));
        }

        return new Document(...$nodes);
    }
}
