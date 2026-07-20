<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\PositionedText;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(PositionedTextElement::class)]
class PositionedTextElementTest extends TestCase {
    public function testGetHeightForHorizontalText(): void {
        static::assertSame(
            10.0,
            (new PositionedTextElement([new TextSegment(new TextStringValue('(a)'), null)], new TransformationMatrix(1, 0, 0, 1, 0, 0), new TextState(null, 10)))->getHeight(),
        );
    }

    public function testGetHeightUsesVerticalScale(): void {
        static::assertSame(
            30.0,
            (new PositionedTextElement([new TextSegment(new TextStringValue('(a)'), null)], new TransformationMatrix(2, 0, 0, 3, 0, 0), new TextState(null, 10)))->getHeight(),
        );
    }

    public function testGetHeightStaysNonZeroForTextRotatedOntoAVerticalBaseline(): void {
        // (0, -1, 1, 0, ...) rotates text onto a vertical baseline, where its height equals the font size.
        static::assertSame(
            10.0,
            (new PositionedTextElement([new TextSegment(new TextStringValue('(a)'), null)], new TransformationMatrix(0, -1, 1, 0, 0, 0), new TextState(null, 10)))->getHeight(),
        );
    }
}
