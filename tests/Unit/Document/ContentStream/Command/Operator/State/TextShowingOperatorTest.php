<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;

#[CoversClass(TextShowingOperator::class)]
class TextShowingOperatorTest extends TestCase {
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
}
