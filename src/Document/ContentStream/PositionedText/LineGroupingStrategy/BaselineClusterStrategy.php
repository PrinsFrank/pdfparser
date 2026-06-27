<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\Vector;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/**
 * Orientation-agnostic, two-pass line grouping (the default; {@see TextOverlapStrategy} remains as an
 * axis-aligned alternative).
 *
 * Pass 1 clusters text elements into lines by their actual baseline rather than a fixed horizontal/vertical
 * axis: two text elements share a line when their baseline directions point nearly the same way (within
 * $angleToleranceDegrees) and their glyph extents overlap by at least $overlapPercentage measured along the
 * baseline normal. For horizontal text the baseline normal is the Y axis, so this reduces exactly to
 * {@see TextOverlapStrategy}'s Y-overlap test, including keeping an enclosed subscript (the "2" in "CO2") on its
 * line. Within a line the text elements are ordered along the baseline's own direction vector, so reading order
 * follows the text's advance direction at any rotation (90 and 270 degree text each read the right way round).
 *
 * Pass 2 reassembles the lines into reading order in three steps, so that a rotated block stays contiguous and
 * reads the right way round at any angle:
 *
 *  - segment the lines into blocks: lines are first bucketed by baseline orientation (a rotated overlay and the
 *    horizontal body it crosses never share a block, so their lines do not interleave), then each bucket is split
 *    into contiguous stacks along the baseline normal (a gap of more than a few line heights along that normal --
 *    a paragraph break, or a region stacked above or below -- starts a new block; side-by-side columns of the same
 *    orientation are not separated here, see {@see segmentIntoBlocks()});
 *  - within a block, order the lines top to bottom along the block's own baseline normal (line N+1 sits one
 *    leading along -normal from line N), so a block rotated past vertical reads top to bottom in its own frame
 *    rather than being reversed by the page Y axis; for horizontal text the normal is the page Y axis, so this is
 *    simply descending offsetY;
 *  - order the blocks against each other by their highest point on the page (largest offsetY), keeping the overall
 *    top-to-bottom flow; ties keep document order.
 *
 * For horizontal text the baseline normal is the page Y axis and there is a single orientation bucket, so across a
 * page of upright text this is byte-identical to {@see TextOverlapStrategy}; the strategies differ only where text
 * is rotated.
 */
class BaselineClusterStrategy implements LineGroupingStrategy {
    /**
     * @param int<0, 100> $overlapPercentage  minimum percentage two glyph extents must overlap, measured across the
     *                                        baseline, to share a line
     * @param float $angleToleranceDegrees    maximum angle between two baselines for them to count as the same
     *                                        direction (and so be eligible to share a line or block)
     * @param float $blockGapLineHeights      how many line heights two stacked lines may be apart, measured along the
     *                                        baseline normal, before they are treated as separate blocks
     */
    public function __construct(
        private readonly int $overlapPercentage = 90,
        private readonly float $angleToleranceDegrees = 3.0,
        private readonly float $blockGapLineHeights = 2.5,
    ) {}

    #[Override]
    public function group(array $positionedTextElements): iterable {
        // angle <= tolerance  <=>  dot of the unit baseline vectors >= cos(tolerance); avoids an acos per pair. Both
        // the line clustering and the block bucketing compare baseline directions this way against this threshold.
        $cosTolerance = cos(deg2rad($this->angleToleranceDegrees));

        $lines = $this->clusterIntoLines($positionedTextElements, $cosTolerance);
        foreach ($this->orderBlocks($this->segmentIntoBlocks($lines, $cosTolerance)) as $block) {
            foreach ($this->orderLinesWithinBlock($block) as $line) {
                yield $line->elements;
            }
        }
    }

    /**
     * Pass 1: cluster runs into lines by their actual baseline. A run joins the seed's line when its baseline
     * points nearly the same way (within $angleToleranceDegrees) and its glyph extent overlaps the seed's by at
     * least $overlapPercentage measured along the baseline normal, or it is an enclosed subscript of the line.
     * Within a line the runs are ordered along the baseline (reading direction); each Line carries the seed's
     * baseline direction and origin, its height, its highest point on the page, and its document position.
     *
     * @param list<PositionedTextElement> $positionedTextElements
     * @param float                       $cosTolerance cosine of $angleToleranceDegrees; see {@see group()}
     * @return list<Line>
     */
    private function clusterIntoLines(array $positionedTextElements, float $cosTolerance): array {
        $documentOrder = [];
        foreach ($positionedTextElements as $index => $element) {
            $documentOrder[spl_object_id($element)] = $index;
        }

        usort(
            $positionedTextElements,
            static fn(PositionedTextElement $a, PositionedTextElement $b): int => $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY,
        );

        $count = count($positionedTextElements);

        // Precompute each text element's baseline direction, height and origin. The clustering scan below is O(n^2),
        // so recomputing these (each involves a hypot) per pair would dominate the cost on large pages.
        $dirX = $dirY = $height = $offsetX = $offsetY = [];
        for ($i = 0; $i < $count; $i++) {
            $matrix = $positionedTextElements[$i]->absoluteMatrix;
            $baseline = $matrix->baselineVector();
            $unit = $baseline->length() === 0.0 ? new Vector(1.0, 0.0) : $baseline->normalized();
            $dirX[$i] = $unit->x;
            $dirY[$i] = $unit->y;
            $height[$i] = $positionedTextElements[$i]->getHeight();
            $offsetX[$i] = $matrix->offsetX;
            $offsetY[$i] = $matrix->offsetY;
        }

        /** @var array<int, true> $processed */
        $processed = [];
        /** @var list<Line> $lines */
        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            if (isset($processed[$i])) {
                continue;
            }

            $ux = $dirX[$i];
            $uy = $dirY[$i];
            $normalX = -$uy;
            $normalY = $ux;
            $seedHeight = $height[$i];
            $seedPerpendicular = $offsetX[$i] * $normalX + $offsetY[$i] * $normalY;
            $lineIndices = [$i];
            $processed[$i] = true;
            $lineHeight = $seedHeight;
            // Track the line's extent along the baseline so an enclosed subscript can be recognised below.
            $lineStart = $lineEnd = $offsetX[$i] * $ux + $offsetY[$i] * $uy;
            for ($j = $i + 1; $j < $count; $j++) {
                if (isset($processed[$j])) {
                    continue;
                }

                if ($ux * $dirX[$j] + $uy * $dirY[$j] < $cosTolerance) {
                    continue; // baseline points in a different direction
                }

                $candidatePerpendicular = $offsetX[$j] * $normalX + $offsetY[$j] * $normalY;
                $overlap = min($seedPerpendicular + $seedHeight, $candidatePerpendicular + $height[$j])
                    - max($seedPerpendicular, $candidatePerpendicular);
                $smallestHeight = min($seedHeight, $height[$j]);
                $belongsOnLine = $smallestHeight !== 0.0 && $overlap / $smallestHeight * 100 >= $this->overlapPercentage;

                // A subscript such as the "2" in "CO2" has a smaller font and a baseline shifted off the line, so it
                // overlaps too little to cluster on its own. Keep it on the line when it partially overlaps and sits
                // within the run already collected along the baseline -- it is enclosed by surrounding text.
                $candidateAlongBaseline = $offsetX[$j] * $ux + $offsetY[$j] * $uy;
                $isEnclosedSubscript = $overlap > 0.0
                    && $height[$j] < $seedHeight
                    && $candidateAlongBaseline >= $lineStart
                    && $candidateAlongBaseline <= $lineEnd;

                if ($belongsOnLine || $isEnclosedSubscript) {
                    $lineIndices[] = $j;
                    $processed[$j] = true;
                    $lineHeight = max($lineHeight, $height[$j]);
                    $lineStart = min($lineStart, $candidateAlongBaseline);
                    $lineEnd = max($lineEnd, $candidateAlongBaseline);
                }
            }

            // The seed's baseline direction and origin represent the whole line; Line::fromElements orders the
            // elements along that direction and captures the line's height, highest point and document position.
            $elements = array_map(static fn(int $index): PositionedTextElement => $positionedTextElements[$index], $lineIndices);
            $lines[] = Line::fromElements(
                $elements,
                new Vector($ux, $uy),
                new Vector($offsetX[$i], $offsetY[$i]),
                $lineHeight,
                $documentOrder,
            );
        }

        return $lines;
    }

    /**
     * Pass 2, steps 1 and 2: group lines into blocks. Lines are first bucketed by baseline orientation (so a
     * rotated overlay never lands in the same block as the horizontal body it is drawn over), then each bucket is
     * split along its baseline normal wherever the gap between consecutive lines exceeds {@see $blockGapLineHeights}
     * line heights -- a paragraph break, or a region stacked above or below, rather than the next line of a
     * paragraph. The lines are ordered into reading order later, per block, by {@see orderLinesWithinBlock()}.
     *
     * The split is measured along the normal (the stacking direction) only, so it detects vertical gaps between
     * stacked lines, never horizontal gaps between columns: side-by-side columns of the same orientation are not
     * separated here -- and where their lines share a baseline position they are already merged into one line by
     * {@see clusterIntoLines()} and read across the columns row by row. The only side-by-side separation the
     * strategy makes is by orientation, in the bucketing above. Multi-column layout is a known limitation; see
     * SPACE-DETECTION.md.
     *
     * @param list<Line> $lines
     * @return list<Block>
     */
    private function segmentIntoBlocks(array $lines, float $cosTolerance): array {
        // Bucket lines by baseline orientation; the first line in a bucket fixes its direction. A rotated overlay
        // and the horizontal body it crosses fall into different buckets, so their lines never interleave.
        /** @var list<non-empty-list<Line>> $buckets */
        $buckets = [];
        foreach ($lines as $line) {
            foreach ($buckets as $index => $bucket) {
                if ($line->direction->dot($bucket[0]->direction) >= $cosTolerance) {
                    $buckets[$index][] = $line;
                    continue 2;
                }
            }

            $buckets[] = [$line];
        }

        $blocks = [];
        foreach ($buckets as $bucket) {
            $normal = $bucket[0]->direction->normal();
            // Sort along the baseline normal only to find the gaps between stacked lines; the direction here does
            // not set reading order (that is decided per block below), it only makes consecutive lines adjacent.
            usort(
                $bucket,
                static fn(Line $a, Line $b): int => $b->positionAlong($normal) <=> $a->positionAlong($normal),
            );

            $blockLines = [];
            $previousPosition = null;
            $previousHeight = 0.0;
            foreach ($bucket as $line) {
                $position = $line->positionAlong($normal);
                // A gap of more than a few line heights along the normal -- a paragraph break, or a region stacked
                // above or below -- ends a block. (Horizontal gaps between columns are not seen here; see the docblock.)
                if ($previousPosition !== null
                    && $previousPosition - $position > max($previousHeight, $line->height) * $this->blockGapLineHeights
                ) {
                    $blocks[] = new Block($normal, $blockLines);
                    $blockLines = [];
                }

                $blockLines[] = $line;
                $previousPosition = $position;
                $previousHeight = $line->height;
            }

            // The bucket is non-empty, so the last stack always has at least the final line.
            $blocks[] = new Block($normal, $blockLines);
        }

        return $blocks;
    }

    /**
     * Orders the lines of a single block into reading order along the block's own baseline normal (line N+1 sits
     * one leading along -normal from line N). For horizontal text the normal is the page Y axis, so this is just
     * descending offsetY (top to bottom); for a block rotated past vertical it follows the block's own frame, which
     * is what keeps such a block from coming out reversed by the page Y axis. Ties keep document order.
     *
     * @return list<Line>
     */
    private function orderLinesWithinBlock(Block $block): array {
        $lines = $block->lines;
        if (count($lines) <= 1) {
            return $lines;
        }

        $normal = $block->normal;
        usort(
            $lines,
            static function (Line $a, Line $b) use ($normal): int {
                $byPosition = $b->positionAlong($normal) <=> $a->positionAlong($normal);
                if ($byPosition !== 0) {
                    return $byPosition;
                }

                return $a->documentPosition <=> $b->documentPosition;
            },
        );

        return $lines;
    }

    /**
     * Pass 2, step 3: order whole blocks against each other by their highest point on the page (largest offsetY);
     * ties keep document order. Blocks are atomic here, so a rotated overlay stays contiguous instead of
     * interleaving with the lines it overlaps.
     *
     * @param list<Block> $blocks
     * @return list<Block>
     */
    private function orderBlocks(array $blocks): array {
        usort(
            $blocks,
            static function (Block $a, Block $b): int {
                if (($byTop = $b->top <=> $a->top) !== 0) {
                    return $byTop;
                }

                return $a->documentPosition <=> $b->documentPosition;
            },
        );

        return $blocks;
    }

    /**
     * Whether a space belongs between two consecutive text elements on a line, measured along the previous
     * element's baseline. For horizontal text this reduces exactly to the axis-aligned advance-width test of
     * {@see MatrixOffsetSpacing}; for any other rotation the gap is taken along the baseline direction (always
     * positive in reading order, so it is direction-agnostic).
     *
     * @throws PdfParserException
     */
    #[Override]
    public function requiresSpaceBetween(PositionedTextElement $previous, PositionedTextElement $current, Document $document, Page $page): bool {
        $baseline = $previous->absoluteMatrix->baselineVector();
        $baselineScale = $baseline->length();
        $unit = $baselineScale === 0.0 ? new Vector(1.0, 0.0) : $baseline->normalized();

        $gap = new Vector(
            $current->absoluteMatrix->offsetX - $previous->absoluteMatrix->offsetX,
            $current->absoluteMatrix->offsetY - $previous->absoluteMatrix->offsetY,
        );
        $gapAlongBaseline = $gap->dot($unit);

        // Measure the previous element's advance along the baseline by projecting its advance vector onto the
        // baseline unit direction (a signed dot product, positive in reading order at any rotation). With an
        // accurate advance the within-word residual collapses near zero, so a single WORD_BREAK_THRESHOLD fraction
        // of the em separates word breaks from kerning -- the same comparison the axis-aligned strategies make for
        // upright text.
        $advanceWidth = $previous->getAdvanceWidth($document, $page, $unit);
        $threshold = ($previous->textState->fontSize ?? 10)
            * $baselineScale
            * ($previous->textState->scale / 100)
            * ContentStream::WORD_BREAK_THRESHOLD;

        return $gapAlongBaseline - $advanceWidth >= $threshold;
    }
}
