<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown\Table;

use PrinsFrank\MarkDownDom\Node\Block\Table\Table;

readonly class ExtractedTable {
    /** @param list<int> $positionedElementIds */
    public function __construct(
        public Table $table,
        public array $positionedElementIds,
    ) {}
}
