<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown\Table;

use PrinsFrank\MarkDownDom\Node\Block\Table\Table;
use PrinsFrank\MarkDownDom\Node\Block\Table\TableCell;
use PrinsFrank\MarkDownDom\Node\Block\Table\TableRow;
use PrinsFrank\MarkDownDom\Node\Inline\Text;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Extraction\Text\TextGrouping\LineGrouping\TextOverlapStrategy;

class TableExtractor {
    /**
     * @param list<list<PositionedTextElement>> $lineGroupedElements
     * @return list<ExtractedTable>
     */
    public static function extract(array $lineGroupedElements, Page $page): array {
        $tables = $tableRows = $itemsInTable = [];
        $currentTableData = null;
        foreach ($lineGroupedElements as $positionedTextElementsForLine) {
            if ($currentTableData !== null) {
                foreach ($positionedTextElementsForLine as $positionedTextElementForLine) {
                    if ($currentTableData->canFitElement($positionedTextElementForLine, $page) === false) {
                        if (count($tableRows) >= 2) {
                            $tables[] = new ExtractedTable(new Table(...$tableRows), $itemsInTable);
                        }
                        $itemsInTable = [];
                        $tableRows = [];
                        $currentTableData = null;
                        break;
                    }
                }
            }

            if ($currentTableData === null) {
                if (count($positionedTextElementsForLine) === 1) {
                    continue;
                }

                $currentTableData = new TableStructure(
                    ...array_map(fn(PositionedTextElement $positionedTextElement): ColumnBounds => new ColumnBounds($positionedTextElement), $positionedTextElementsForLine),
                );

                $cells = [];
                foreach ($positionedTextElementsForLine as $element) {
                    $cells[] = new TableCell(new Text($element->getText($page)));
                    $itemsInTable[] = spl_object_id($element);
                }
                $tableRows[] = new TableRow(...$cells);

                continue;
            }

            $whiteSpaceOnly = true;
            $cells = $usedElementIndices = [];
            foreach ($currentTableData->columns as $column) {
                $cellText = '';
                foreach ($positionedTextElementsForLine as $i => $positionedTextElementForLine) {
                    if (in_array($i, $usedElementIndices, true)) {
                        continue;
                    }

                    if ($column->canFitElement($positionedTextElementForLine, $page)) {
                        $elementText = $positionedTextElementForLine->getText($page);
                        if (trim($elementText) !== '') {
                            $whiteSpaceOnly = false;
                        }
                        $cellText .= $elementText;
                        $column->addElement($positionedTextElementForLine);
                        $itemsInTable[] = spl_object_id($positionedTextElementForLine);
                        $usedElementIndices[] = $i;
                    }
                }

                $cells[] = new TableCell(new Text($cellText));
            }

            if ($whiteSpaceOnly === false) {
                $tableRows[] = new TableRow(...$cells);
            }
        }

        if (count($tableRows) >= 2) {
            $tables[] = new ExtractedTable(new Table(...$tableRows), $itemsInTable);
        }

        return $tables;
    }
}
