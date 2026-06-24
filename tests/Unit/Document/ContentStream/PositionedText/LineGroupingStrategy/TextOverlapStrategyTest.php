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

    public function testKeepsAnEnclosedSubscriptOnItsLine(): void {
        // A subscript like the "2" in "CO2" has a smaller font and a baseline shifted down, so it overlaps the line
        // too little to clear the threshold on its own. Because it is enclosed horizontally by text on the line
        // ("CO" before it, "-Bilanz" after it) it still belongs to that line rather than forming a line of its own.
        $left = new PositionedTextElement('(CO)', new TransformationMatrix(1, 0, 0, 1, 50, 100), new TextState(null, 10));
        $subscript = new PositionedTextElement('(2)', new TransformationMatrix(1, 0, 0, 1, 60, 98), new TextState(null, 7));
        $right = new PositionedTextElement('(-Bilanz)', new TransformationMatrix(1, 0, 0, 1, 70, 100), new TextState(null, 10));

        static::assertSame(
            [[$left, $subscript, $right]],
            iterator_to_array((new TextOverlapStrategy())->group([$left, $right, $subscript]), false),
        );
    }

    public function testKeepsASmallerButNotEnclosedLineSeparate(): void {
        // A smaller line that merely sits close (e.g. a subtitle below a heading) overlaps the heading just as much
        // as an enclosed subscript would, but it is not enclosed horizontally by the heading's text, so it must keep
        // its own line instead of being absorbed.
        $heading = new PositionedTextElement('(BERTOSSI)', new TransformationMatrix(1, 0, 0, 1, 20, 800), new TextState(null, 22));
        $subtitle = new PositionedTextElement('(Commande)', new TransformationMatrix(1, 0, 0, 1, 200, 796), new TextState(null, 12));

        static::assertSame(
            [[$heading], [$subtitle]],
            iterator_to_array((new TextOverlapStrategy())->group([$subtitle, $heading]), false),
        );
    }
}
