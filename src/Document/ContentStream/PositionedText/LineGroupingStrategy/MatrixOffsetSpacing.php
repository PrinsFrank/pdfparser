<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/**
 * Axis-aligned space-insertion heuristic shared by the strategies that lay text out along page X
 * ({@see TextOverlapStrategy}, {@see StrictLineGrouping}). A space belongs between two runs when the gap between
 * their X offsets, less the reconstructed advance width of the previous run, reaches a single
 * {@see ContentStream::WORD_BREAK_THRESHOLD} fraction of the em. With an accurate advance the within-word residual
 * collapses near zero, so one threshold separates word breaks from kerning across producers. Holds only for
 * upright text; {@see BaselineClusterStrategy} measures the same comparison along an arbitrary baseline.
 */
trait MatrixOffsetSpacing {
    /** @throws PdfParserException */
    public function requiresSpaceBetween(PositionedTextElement $previous, PositionedTextElement $current, Document $document, Page $page): bool {
        $gap = $current->absoluteMatrix->offsetX
            - $previous->absoluteMatrix->offsetX
            - $previous->getAdvanceWidth($document, $page);

        $threshold = $previous->textState->getFontSize()
            * $previous->absoluteMatrix->scaleX
            * ($previous->textState->scale / 100)
            * ContentStream::WORD_BREAK_THRESHOLD;

        return $gap >= $threshold;
    }
}
