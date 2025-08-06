<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

#[CoversClass(GraphicsStateOperator::class)]
class GraphicsStateOperatorTest extends TestCase {
    public function testApplyToTransformationMatrix(): void {
        static::assertEquals(
            new TransformationMatrix(7.0, 10.0, 15.0, 22.0, 28.0, 40.0),
            GraphicsStateOperator::ModifyCurrentTransformationMatrix->applyToTransformationMatrix(
                '1 2 3 4 5 6',
                new TransformationMatrix(1.0, 2.0, 3.0, 4.0, 5.0, 6.0),
            )
        );
        static::assertEquals(
            new TransformationMatrix(7.0, 10.0, 15.0, 22.0, 28.0, 40.0),
            GraphicsStateOperator::ModifyCurrentTransformationMatrix->applyToTransformationMatrix(
                '1  2  3  4  5  6',
                new TransformationMatrix(1.0, 2.0, 3.0, 4.0, 5.0, 6.0),
            )
        );
        static::assertEquals(
            new TransformationMatrix(7.0, 10.0, 15.0, 22.0, 28.0, 40.0),
            GraphicsStateOperator::ModifyCurrentTransformationMatrix->applyToTransformationMatrix(
                '1   2   3   4   5   6',
                new TransformationMatrix(1.0, 2.0, 3.0, 4.0, 5.0, 6.0),
            )
        );
    }
}
