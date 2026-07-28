<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown\Table;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

class TableStructure {
    /** @var ColumnBounds[] */
    public array $columns;

    public function __construct(
        ColumnBounds... $columns,
    ) {
        $this->columns = $columns;
    }

    public function canFitElement(PositionedTextElement $positionedTextElement, Page $page): bool {
        foreach ($this->columns as $column) {
            if ($column->canFitElement($positionedTextElement, $page)) {
                return true;
            }
        }

        return false;
    }
}
