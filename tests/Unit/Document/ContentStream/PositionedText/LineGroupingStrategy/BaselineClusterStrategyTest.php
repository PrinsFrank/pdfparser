<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\BaselineClusterStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\Block;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\Line;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\TextOverlapStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

#[CoversClass(BaselineClusterStrategy::class)]
#[CoversClass(Block::class)]
#[CoversClass(Line::class)]
class BaselineClusterStrategyTest extends TestCase {
    public function testGroupsHorizontalTextByVerticalOverlap(): void {
        // For horizontal text the baseline normal is the Y axis, so this matches TextOverlapStrategy.
        $left = new PositionedTextElement('(left)', new TransformationMatrix(1, 0, 0, 1, 10, 700), new TextState(null, 10));
        $right = new PositionedTextElement('(right)', new TransformationMatrix(1, 0, 0, 1, 80, 700), new TextState(null, 10));
        $below = new PositionedTextElement('(below)', new TransformationMatrix(1, 0, 0, 1, 10, 600), new TextState(null, 10));

        static::assertSame(
            [[$left, $right], [$below]],
            iterator_to_array((new BaselineClusterStrategy())->group([$right, $below, $left]), false),
        );
    }

    public function testKeepsAnEnclosedSubscriptOnItsLine(): void {
        // A subscript like the "2" in "CO2" has a smaller font and a baseline shifted down, so it overlaps the line
        // too little to clear the threshold on its own. Because it is enclosed along the baseline by text on the
        // line ("CO" before it, "-Bilanz" after it) it still belongs to that line, as it does under
        // TextOverlapStrategy.
        $left = new PositionedTextElement('(CO)', new TransformationMatrix(1, 0, 0, 1, 50, 100), new TextState(null, 10));
        $subscript = new PositionedTextElement('(2)', new TransformationMatrix(1, 0, 0, 1, 60, 98), new TextState(null, 7));
        $right = new PositionedTextElement('(-Bilanz)', new TransformationMatrix(1, 0, 0, 1, 70, 100), new TextState(null, 10));

        static::assertSame(
            [[$left, $subscript, $right]],
            iterator_to_array((new BaselineClusterStrategy())->group([$left, $right, $subscript]), false),
        );
    }

    public function testReassemblesDiagonallyRotatedWordIntoOneLine(): void {
        // A word rotated 45 degrees, drawn glyph by glyph along its baseline. The axis-aligned strategy fragments
        // it; clustering along the actual baseline keeps it as one line.
        $cos = cos(deg2rad(45));
        $sin = sin(deg2rad(45));
        $glyph = static fn(int $i, string $text): PositionedTextElement => new PositionedTextElement(
            $text,
            new TransformationMatrix($cos, -$sin, $sin, $cos, 100 + $i * 8 * $cos, 100 - $i * 8 * $sin),
            new TextState(null, 10),
        );
        $w = $glyph(0, '(W)');
        $o = $glyph(1, '(O)');
        $r = $glyph(2, '(R)');

        static::assertSame(
            [[$w, $o, $r]],
            iterator_to_array((new BaselineClusterStrategy())->group([$r, $w, $o]), false),
        );

        // Guards the reason this strategy exists: the axis-aligned strategy cannot keep the diagonal glyphs together.
        static::assertGreaterThan(
            1,
            count(iterator_to_array((new TextOverlapStrategy())->group([$r, $w, $o]), false)),
        );
    }

    public function testReassemblesWordRotatedPast90Degrees(): void {
        // 210 degrees: baseline points down-left (scaleX < 0). Grouping and reading order are angle-agnostic,
        // so a word past vertical still reassembles in order.
        $cos = cos(deg2rad(210));
        $sin = sin(deg2rad(210));
        $glyph = static fn(int $i, string $text): PositionedTextElement => new PositionedTextElement(
            $text,
            new TransformationMatrix($cos, -$sin, $sin, $cos, 200 + $i * 8 * $cos, 200 - $i * 8 * $sin),
            new TextState(null, 10),
        );
        $a = $glyph(0, '(A)');
        $b = $glyph(1, '(B)');
        $c = $glyph(2, '(C)');

        static::assertSame(
            [[$a, $b, $c]],
            iterator_to_array((new BaselineClusterStrategy())->group([$c, $a, $b]), false),
        );
    }

    public function testReadsRotatedWordAlongItsBaselineDirection(): void {
        // 90 degrees counter-clockwise: the baseline advances UP the page, so the word reads bottom to top.
        // Ordering follows the baseline vector, so it is not reversed (TextOverlapStrategy's fixed top-to-bottom
        // sort would reverse it).
        $bottom = new PositionedTextElement('(W)', new TransformationMatrix(0, 1, -1, 0, 300, 500), new TextState(null, 10));
        $middle = new PositionedTextElement('(O)', new TransformationMatrix(0, 1, -1, 0, 300, 560), new TextState(null, 10));
        $top = new PositionedTextElement('(R)', new TransformationMatrix(0, 1, -1, 0, 300, 620), new TextState(null, 10));

        static::assertSame(
            [[$bottom, $middle, $top]],
            iterator_to_array((new BaselineClusterStrategy())->group([$top, $bottom, $middle]), false),
        );
    }

    public function testKeepsParallelBaselinesAtDifferentPositionsSeparate(): void {
        // Two vertical columns at different X offsets are different lines, not one.
        $colA = new PositionedTextElement('(A)', new TransformationMatrix(0, -1, 1, 0, 100, 700), new TextState(null, 10));
        $colB = new PositionedTextElement('(B)', new TransformationMatrix(0, -1, 1, 0, 300, 700), new TextState(null, 10));

        static::assertSame(
            [[$colA], [$colB]],
            iterator_to_array((new BaselineClusterStrategy())->group([$colA, $colB]), false),
        );
    }

    public function testOrdersLinesAtTheSameHeightByDocumentOrder(): void {
        // Two separate lines topped at the same Y: ties keep document order (the right column is given first),
        // rather than being re-ordered left to right.
        $right = new PositionedTextElement('(right)', new TransformationMatrix(0, -1, 1, 0, 300, 700), new TextState(null, 10));
        $left = new PositionedTextElement('(left)', new TransformationMatrix(0, -1, 1, 0, 100, 700), new TextState(null, 10));

        static::assertSame(
            [[$right], [$left]],
            iterator_to_array((new BaselineClusterStrategy())->group([$right, $left]), false),
        );
    }

    public function testOrdersLinesOfABlockRotatedPastVerticalInReadingOrder(): void {
        // A three-line block whose baseline points down-left (200 degrees): its line-feed direction has an UPWARD
        // page-Y component, so successive reading lines have *increasing* abs(offsetY). Ordering lines by
        // abs(offsetY) would reverse the block; ordering along the block's own baseline normal keeps reading order.
        $cos = cos(deg2rad(200));
        $sin = sin(deg2rad(200));
        $normalX = -$sin; // baseline normal = (-uy, ux); line N+1 sits one leading along -normal from line N
        $normalY = $cos;
        $leading = 15.0;
        $line = static fn(int $k, string $text): PositionedTextElement => new PositionedTextElement(
            $text,
            new TransformationMatrix($cos, $sin, -$sin, $cos, 300 - $k * $leading * $normalX, 400 - $k * $leading * $normalY),
            new TextState(null, 12),
        );
        $first = $line(0, '(A)');
        $second = $line(1, '(B)');
        $third = $line(2, '(C)');

        static::assertSame(
            [[$first], [$second], [$third]],
            iterator_to_array((new BaselineClusterStrategy())->group([$third, $first, $second]), false),
        );
    }

    public function testOrdersHorizontalLinesBelowTheOriginTopToBottom(): void {
        // A page whose MediaBox origin sits above its content (negative lower-left, e.g. [0 -792 612 0]) has
        // all-negative offsetY, with the topmost line the least negative. Ordering by descending offsetY reads it
        // top to bottom; ordering by abs(offsetY) would reverse it.
        $top = new PositionedTextElement('(top)', new TransformationMatrix(1, 0, 0, 1, 50, -100), new TextState(null, 10));
        $middle = new PositionedTextElement('(middle)', new TransformationMatrix(1, 0, 0, 1, 50, -120), new TextState(null, 10));
        $bottom = new PositionedTextElement('(bottom)', new TransformationMatrix(1, 0, 0, 1, 50, -140), new TextState(null, 10));

        static::assertSame(
            [[$top], [$middle], [$bottom]],
            iterator_to_array((new BaselineClusterStrategy())->group([$bottom, $top, $middle]), false),
        );
    }

    public function testKeepsARotatedOverlayContiguousInsteadOfInterleavingWithHorizontalBody(): void {
        // Three horizontal body lines with a two-line 45-degree overlay whose abs(offsetY) values fall *between*
        // the body lines. A single global sort by offsetY drops the overlay lines into the gaps (body, overlay,
        // body, overlay, body); bucketing by orientation keeps the overlay as one contiguous block.
        $bodyTop = new PositionedTextElement('(top)', new TransformationMatrix(1, 0, 0, 1, 100, 700), new TextState(null, 12));
        $bodyMiddle = new PositionedTextElement('(middle)', new TransformationMatrix(1, 0, 0, 1, 100, 690), new TextState(null, 12));
        $bodyBottom = new PositionedTextElement('(bottom)', new TransformationMatrix(1, 0, 0, 1, 100, 680), new TextState(null, 12));

        $cos = cos(deg2rad(45));
        $sin = sin(deg2rad(45));
        $overlayFirst = new PositionedTextElement('(O1)', new TransformationMatrix($cos, $sin, -$sin, $cos, 200, 695), new TextState(null, 12));
        $overlaySecond = new PositionedTextElement('(O2)', new TransformationMatrix($cos, $sin, -$sin, $cos, 210, 685), new TextState(null, 12));

        static::assertSame(
            [[$bodyTop], [$bodyMiddle], [$bodyBottom], [$overlayFirst], [$overlaySecond]],
            iterator_to_array(
                (new BaselineClusterStrategy())->group([$overlaySecond, $bodyBottom, $overlayFirst, $bodyTop, $bodyMiddle]),
                false,
            ),
        );
    }

    public function testInsertsSpaceAlongVerticalBaselineOnlyForAWideGap(): void {
        // Spacing runs through the same advance-width test at any rotation: a space belongs when the gap along the
        // baseline, minus the previous element's advance, clears a single WORD_BREAK_THRESHOLD (0.25) fraction of
        // the em. Here the baseline points straight down (matrix 0,-1,1,0 -> baselineScale 1), so the threshold is
        // 10 * 1 * 0.25 = 2.5. With the advance stubbed to 3, a 100-unit drop (700 -> 600) clears it
        // (100 - 3 >= 2.5) but a 5-unit drop (700 -> 695) does not (5 - 3 < 2.5).
        $document = self::createStub(Document::class);
        $font = self::createStub(Font::class);
        $font->method('getWidthForChars')->willReturn(3.0);
        $fontDictionary = self::createStub(Dictionary::class);
        $fontDictionary->method('getObjectForReference')->willReturn($font);
        $page = self::createStub(Page::class);
        $page->method('getFontDictionary')->willReturn($fontDictionary);
        $strategy = new BaselineClusterStrategy();

        $previous = new PositionedTextElement('(A)', new TransformationMatrix(0, -1, 1, 0, 300, 700), new TextState(DictionaryKey::FONT, 10));
        $farBelow = new PositionedTextElement('(B)', new TransformationMatrix(0, -1, 1, 0, 300, 600), new TextState(DictionaryKey::FONT, 10));
        $justBelow = new PositionedTextElement('(B)', new TransformationMatrix(0, -1, 1, 0, 300, 695), new TextState(DictionaryKey::FONT, 10));

        static::assertTrue($strategy->requiresSpaceBetween($previous, $farBelow, $document, $page));
        static::assertFalse($strategy->requiresSpaceBetween($previous, $justBelow, $document, $page));
    }
}
