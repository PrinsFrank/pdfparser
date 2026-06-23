<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\PositionedText\LineGroupingStrategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\TextOverlapStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

#[CoversClass(TextOverlapStrategy::class)]
class TextOverlapStrategyTest extends TestCase {
    public function testOrdersLinesTopToBottom(): void {
        $top = new PositionedTextElement('(top)', new TransformationMatrix(1, 0, 0, 1, 100, 700), new TextState(null, 10));
        $bottom = new PositionedTextElement('(bottom)', new TransformationMatrix(1, 0, 0, 1, 100, 100), new TextState(null, 10));

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
        $top = new PositionedTextElement('(top)', new TransformationMatrix(1, 0, 0, 1, 100, -50), new TextState(null, 10));
        $bottom = new PositionedTextElement('(bottom)', new TransformationMatrix(1, 0, 0, 1, 100, -300), new TextState(null, 10));

        static::assertSame(
            [[$top], [$bottom]],
            iterator_to_array((new TextOverlapStrategy())->group([$bottom, $top]), false),
        );
    }
}
