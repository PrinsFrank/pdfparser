<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\MatrixOffsetSpacing;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\TextOverlapStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

#[CoversClass(TextOverlapStrategy::class)]
#[CoversTrait(MatrixOffsetSpacing::class)]
class TextOverlapStrategyTest extends TestCase {
    public function testOrdersLinesTopToBottom(): void {
        $top = new PositionedTextElement([new TextSegment(new TextStringValue('(top)'), null)], new TransformationMatrix(1, 0, 0, 1, 100, 700), new TextState(null, 10));
        $bottom = new PositionedTextElement([new TextSegment(new TextStringValue('(bottom)'), null)], new TransformationMatrix(1, 0, 0, 1, 100, 100), new TextState(null, 10));

        static::assertSame(
            [[$top], [$bottom]],
            iterator_to_array((new TextOverlapStrategy())->group([$bottom, $top]), false),
        );
    }

    public function testOrdersLinesTopToBottomOnANegativeOriginPage(): void {
        // A page whose MediaBox origin sits above its content (negative lower-left, e.g. [0 -792 612 0]) has
        // all-negative offsetY, with the topmost line the least negative. Ordering by descending offsetY keeps
        // reading order; ordering by abs(offsetY) -- as the code did before text positions were composed correctly
        // -- would reverse the page.
        $top = new PositionedTextElement([new TextSegment(new TextStringValue('(top)'), null)], new TransformationMatrix(1, 0, 0, 1, 100, -50), new TextState(null, 10));
        $bottom = new PositionedTextElement([new TextSegment(new TextStringValue('(bottom)'), null)], new TransformationMatrix(1, 0, 0, 1, 100, -300), new TextState(null, 10));

        static::assertSame(
            [[$top], [$bottom]],
            iterator_to_array((new TextOverlapStrategy())->group([$bottom, $top]), false),
        );
    }

    public function testInsertsSpaceForAGapWiderThanTheReconstructedAdvance(): void {
        // The axis-aligned MatrixOffsetSpacing heuristic inserts a space when the horizontal gap, minus the
        // previous run's reconstructed advance, clears a single WORD_BREAK_THRESHOLD (0.25) fraction of the em.
        // With scaleX 1, font size 10 and the advance stubbed to 3, the threshold is 10 * 1 * 0.25 = 2.5: a run
        // starting at X 400 clears it (400 - 300 - 3 >= 2.5) but one at X 305 does not (305 - 300 - 3 < 2.5).
        $document = self::createStub(Document::class);
        $font = self::createStub(Font::class);
        $font->method('getWidthForChars')->willReturn(3.0);
        $fontDictionary = self::createStub(Dictionary::class);
        $fontDictionary->method('getObjectForReference')->willReturn($font);
        $page = self::createStub(Page::class);
        $page->method('getFontDictionary')->willReturn($fontDictionary);
        $strategy = new TextOverlapStrategy();

        $previous = new PositionedTextElement([new TextSegment(new TextStringValue('(A)'), null)], new TransformationMatrix(1, 0, 0, 1, 300, 700), new TextState(DictionaryKey::FONT, 10));
        $farRight = new PositionedTextElement([new TextSegment(new TextStringValue('(B)'), null)], new TransformationMatrix(1, 0, 0, 1, 400, 700), new TextState(DictionaryKey::FONT, 10));
        $justRight = new PositionedTextElement([new TextSegment(new TextStringValue('(B)'), null)], new TransformationMatrix(1, 0, 0, 1, 305, 700), new TextState(DictionaryKey::FONT, 10));

        static::assertTrue($strategy->requiresSpaceBetween($previous, $farRight, $document, $page));
        static::assertFalse($strategy->requiresSpaceBetween($previous, $justRight, $document, $page));
    }
}
