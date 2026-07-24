<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Text\TextGrouping\LineGrouping;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

interface LineGroupingStrategy {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return iterable<list<PositionedTextElement>>
     */
    public function group(array $positionedTextElements): iterable;
}
