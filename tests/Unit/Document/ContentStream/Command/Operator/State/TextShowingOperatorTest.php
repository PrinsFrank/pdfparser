<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Font;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(TextShowingOperator::class)]
class TextShowingOperatorTest extends TestCase {
    public function testArrayAcceptsPdfWhitespaceAndPreservesNullInsideLiteral(): void {
        $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
            "[(a)\x00-10\t\n\f\r (b\x00c)]",
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TextState(null, 12),
            new ColorState(),
        );

        static::assertSame(-10.0, $element->textSegments[0]->offset);
        static::assertSame('a', $element->textSegments[0]->textString->getBinaryString());
        static::assertSame("b\x00c", $element->textSegments[1]->textString->getBinaryString());
    }

    public function testSingleHexStringAndGeneralOperandsKeepTheirBytesAndOffsets(): void {
        foreach ([
            '<>' => [['', null]],
            '<4142>' => [['AB', null]],
            "<4\x00\t\n\f\r 1 4>" => [["A@", null]],
            '<41> -20' => [['A', -20.0]],
            '<41><42>' => [['A', null], ['B', null]],
        ] as $operands => $expected) {
            $element = TextShowingOperator::SHOW->getPositionedTextElement(
                $operands,
                new TransformationMatrix(1, 0, 0, 1, 20, 30),
                new TransformationMatrix(2, 0, 0, 2, 0, 0),
                new TextState(null, 12),
                new ColorState(),
            );
            static::assertSame($expected, array_map(static fn(TextSegment $segment): array => [$segment->textString->getBinaryString(), $segment->offset], $element->textSegments));
            static::assertEquals(new TransformationMatrix(2, 0, 0, 2, 40, 60), $element->absoluteMatrix);
        }
    }

    public function testNumericOnlyAndEmptyArrays(): void {
        foreach (["[-250]" => -250.0, "[]" => 0.0, "[+20 -.5 10. (A) -3 -7]" => 19.5] as $operands => $offset) {
            $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement($operands, new TransformationMatrix(1, 0, 0, 1, 0, 0), new TransformationMatrix(1, 0, 0, 1, 0, 0), new TextState(null, 12), new ColorState());
            static::assertSame($offset, $element->getTotalOffset());
        }
    }

    public function testNumericOnlyArrayPreservesTextAdvance(): void {
        $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
            '[-250]',
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TransformationMatrix(2, 0, 0, 2, 0, 0),
            new TextState(null, 12, scale: 50),
            new ColorState(),
        );
        static::assertSame([], $element->getBytes());
        static::assertSame(3.0, $element->getAdvanceWidth(new Font(new Dictionary(null))));
        static::assertSame('', $element->getText(new Font(new Dictionary(null))));
    }

    public function testFirefoxLeadingEmptyLiteralPreservesWordSpacing(): void {
        $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
            '[()-281(Et)]',
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TextState(null, 12),
            new ColorState(),
        );
        static::assertSame(' Et', $element->getText(new Font(new Dictionary(null))));
        static::assertSame(-281.0, $element->getTotalOffset());
    }

    public function testLeadingAdjustmentsAndBalancedLiteralStrings(): void {
        $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
            '[-250 +10 (A(B)C) -1 -2 <44>]',
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TextState(null, 12),
            new ColorState(),
        );
        static::assertSame('', $element->textSegments[0]->textString->getBinaryString());
        static::assertSame(-240.0, $element->textSegments[0]->offset);
        static::assertSame('A(B)C', $element->textSegments[1]->textString->getBinaryString());
        static::assertSame(-3.0, $element->textSegments[1]->offset);
        static::assertSame('D', $element->textSegments[2]->textString->getBinaryString());
    }

    public function testInvalidArrayTokenIsNotSilentlySkipped(): void {
        $this->expectException(ParseFailureException::class);
        TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
            '[(A) /Invalid (B)]',
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TransformationMatrix(1, 0, 0, 1, 0, 0),
            new TextState(null, 12),
            new ColorState(),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAdjustments(): iterable {
        yield 'joined decimals' => ['[1.2.3 (A)]'];
        yield 'joined signs' => ['[1+2 (A)]'];
        yield 'non-PDF whitespace' => ["[\x0B(A)]"];
        yield 'overflow' => ['[' . str_repeat('9', 400) . ' (A)]'];
        yield 'sum overflow' => ['[' . str_repeat('9', 308) . ' ' . str_repeat('9', 308) . ' (A)]'];
    }

    #[DataProvider('invalidAdjustments')]
    public function testInvalidNumericTokensAreRejected(string $operands): void {
        $this->expectException(ParseFailureException::class);
        $matrix = new TransformationMatrix(1, 0, 0, 1, 0, 0);
        TextShowingOperator::SHOW_ARRAY->getPositionedTextElement($operands, $matrix, $matrix, new TextState(null, 12), new ColorState());
    }

    public function testGetPositionedTextElementsFromFigure61FirstExample(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(new LiteralStringValue('AWAY again'), null),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
                '[(AWAY again)]',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }

    public function testGetPositionedTextElementsFromFigure61SecondExample(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(new LiteralStringValue('A'), 120),
                    new TextSegment(new LiteralStringValue('W'), 120),
                    new TextSegment(new LiteralStringValue('A'), 95),
                    new TextSegment(new LiteralStringValue('Y again'), null),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
                '[(A) 120 (W) 120 (A) 95 (Y again)]',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }

    public function testGetPositionedTextElementsWithEscapedCharacters(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(new LiteralStringValue('A\\\\'), 120),
                    new TextSegment(new LiteralStringValue('W\\)'), 120),
                    new TextSegment(new LiteralStringValue('A\\('), 95),
                    new TextSegment(new LiteralStringValue('Y again'), null),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::SHOW_ARRAY->getPositionedTextElement(
                '[(A\\\\) 120 (W\\)) 120 (A\\() 95 (Y again)]',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }
}
