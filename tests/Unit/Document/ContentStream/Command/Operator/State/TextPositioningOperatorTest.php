<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

#[CoversClass(TextPositioningOperator::class)]
class TextPositioningOperatorTest extends TestCase {
    public function testApplyToTransformationMatrix(): void {
        static::assertEquals(
            new TransformationMatrix(1, 0, 0, 1, 100, 100),
            TextPositioningOperator::MOVE_OFFSET
                ->applyToTransformationMatrix('100 100', new TransformationMatrix(1, 0, 0, 1, 0, 0))
        );
        static::assertEquals(
            new TransformationMatrix(1, 0, 0, 1, 100, 100),
            TextPositioningOperator::MOVE_OFFSET
                ->applyToTransformationMatrix('100    100', new TransformationMatrix(1, 0, 0, 1, 0, 0))
        );
        static::assertEquals(
            new TransformationMatrix(1, 0, 0, 1, 100, 100),
            TextPositioningOperator::MOVE_OFFSET
                ->applyToTransformationMatrix(
                    <<<EOD
                    100
                    100
                    EOD,
                    new TransformationMatrix(1, 0, 0, 1, 0, 0),
                )
        );
    }
}
