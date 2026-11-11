<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Stream\FileStream;

#[CoversClass(ContentStreamParser::class)]
#[CoversClass(TextShowingOperator::class)]
class OperandFastPathTest extends TestCase {
    /** @return iterable<string, array{string, string}> */
    public static function hexStrings(): iterable {
        yield 'plain' => ['<414243>', 'ABC'];
        yield 'all PDF whitespace' => ["<41\x00\x09\x0A\x0C\x0D\x204243>", 'ABC'];
        yield 'odd nibble' => ['<414>', "A\x40"];
        yield 'empty' => ['<>', ''];
        yield 'whitespace only' => ["<\x00\x09\x0A\x0C\x0D\x20>", ''];
    }

    #[DataProvider('hexStrings')]
    public function testHexOperandBytesAndMatricesArePreserved(string $operand, string $expected): void {
        $matrix = new TransformationMatrix(1, 0, 0, 1, 20, 30);
        $state = new TextState(null, 12);
        $element = TextShowingOperator::SHOW->getPositionedTextElement($operand, $matrix, new TransformationMatrix(2, 0, 0, 2, 1, 2), $state, new ColorState());
        static::assertCount(1, $element->textSegments);
        static::assertSame($expected, $element->textSegments[0]->textString->getBinaryString());
        static::assertNull($element->textSegments[0]->offset);
        static::assertEquals(new TransformationMatrix(2, 0, 0, 2, 41, 62), $element->absoluteMatrix);
        static::assertSame($state, $element->textState);
    }

    public function testGeneralHexOperandsRetainOffsets(): void {
        $matrix = new TransformationMatrix(1, 0, 0, 1, 0, 0);
        $element = TextShowingOperator::SHOW_ARRAY->getPositionedTextElement('[<41> -20 <42>]', $matrix, $matrix, new TextState(null, 12), new ColorState());
        static::assertCount(2, $element->textSegments);
        static::assertSame('A', $element->textSegments[0]->textString->getBinaryString());
        static::assertSame(-20.0, $element->textSegments[0]->offset);
        static::assertSame('B', $element->textSegments[1]->textString->getBinaryString());
    }

    /** @return iterable<string, array{string, string}> */
    public static function streamOperands(): iterable {
        yield 'adjacent operator' => ['<414243>', '<414243>'];
        yield 'hex whitespace' => ["<41\x00\x09\x0A\x0C\x0D\x204243>", "<41\x00\x09\x0A\x0C\x0D\x204243>"];
        yield 'literal text' => ['(123 abc 456)', '(123 abc 456)'];
        yield 'TJ boundaries' => ['[<41> -123.45 <42>]', '[<41> -123.45 <42>]'];
    }

    #[DataProvider('streamOperands')]
    public function testNumericSpanDoesNotConsumeOperatorsOrStringBoundaries(string $operand, string $expected): void {
        $operator = str_starts_with($operand, '[') ? TextShowingOperator::SHOW_ARRAY : TextShowingOperator::SHOW;
        $stream = FileStream::fromString('BT 123.45 -678.90 Td ' . $operand . $operator->value . " % 12345 comment\nET");
        $object = $this->createMock(CompressedObject::class);
        $object->method('getContent')->willReturn($stream);
        static::assertEquals(
            new ContentStream((new TextObject())
                ->addContentStreamCommand(new ContentStreamCommand(TextPositioningOperator::MOVE_OFFSET, '123.45 -678.90'))
                ->addContentStreamCommand(new ContentStreamCommand($operator, $expected))),
            ContentStreamParser::parse([$object]),
        );
    }
}
