<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;

interface LineGroupingStrategy {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return iterable<list<PositionedTextElement>>
     */
    public function group(array $positionedTextElements): iterable;
}
