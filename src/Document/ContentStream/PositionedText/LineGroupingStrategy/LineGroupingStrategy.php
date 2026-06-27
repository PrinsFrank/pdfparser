<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;

interface LineGroupingStrategy {
    /**
     * @param list<PositionedTextElement> $positionedTextElements
     * @return iterable<list<PositionedTextElement>>
     */
    public function group(array $positionedTextElements): iterable;

    /**
     * Whether a space belongs between two consecutive runs on the same line. The strategy owns this decision
     * because the gap is meaningful only relative to how that strategy laid the runs out.
     *
     * @throws PdfParserException
     */
    public function requiresSpaceBetween(PositionedTextElement $previous, PositionedTextElement $current, Document $document, Page $page): bool;
}
