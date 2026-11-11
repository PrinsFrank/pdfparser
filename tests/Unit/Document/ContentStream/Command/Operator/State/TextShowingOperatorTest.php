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
    public function testGetPositionedTextElementShow(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(null, new LiteralStringValue('AWAY again')),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::SHOW->getPositionedTextElement(
                '(AWAY again)',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }

    public function testGetPositionedTextElementMoveShow(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(null, new LiteralStringValue('AWAY again')),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::MOVE_SHOW->getPositionedTextElement(
                '(AWAY again)',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }

    public function testGetPositionedTextElementMoveShowSpacing(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(null, new LiteralStringValue('AWAY again')),
                ],
                new TransformationMatrix(12, 12, 12, 12, 15, 15),
                $textState,
            ),
            TextShowingOperator::MOVE_SHOW_SPACING->getPositionedTextElement(
                '(AWAY again)',
                $textMatrix,
                $globalTransformationMatrix,
                $textState,
                $colorState,
            ),
        );
    }

    public function testGetPositionedTextElementsFromFigure61FirstExample(): void {
        $textMatrix = new TransformationMatrix(2, 2, 2, 2, 2, 2);
        $globalTransformationMatrix = new TransformationMatrix(3, 3, 3, 3, 3, 3);
        $textState = new TextState(null, null);
        $colorState = new ColorState();

        static::assertEquals(
            new PositionedTextElement(
                [
                    new TextSegment(null, new LiteralStringValue('AWAY again')),
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
                    new TextSegment(null, new LiteralStringValue('A')),
                    new TextSegment(120, new LiteralStringValue('W')),
                    new TextSegment(120, new LiteralStringValue('A')),
                    new TextSegment(95, new LiteralStringValue('Y again')),
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
                    new TextSegment(null, new LiteralStringValue('A\\\\')),
                    new TextSegment(120, new LiteralStringValue('W\\)')),
                    new TextSegment(120, new LiteralStringValue('A\\(')),
                    new TextSegment(95, new LiteralStringValue('Y again')),
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
