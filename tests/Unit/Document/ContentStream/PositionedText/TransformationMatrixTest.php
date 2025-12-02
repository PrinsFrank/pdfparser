<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\PositionedText;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

#[CoversClass(TransformationMatrix::class)]
class TransformationMatrixTest extends TestCase {
    public function testGetAbsoluteY(): void {
        static::assertSame(
            42.0,
            (new TransformationMatrix(0, 0, 0, 6, 0, 7))->getAbsoluteY()
        );
        static::assertSame(
            42.0,
            (new TransformationMatrix(10, 10, 10, 6, 10, 7))->getAbsoluteY()
        );
    }

    public function testGetAbsoluteX(): void {
        static::assertSame(
            42.0,
            (new TransformationMatrix(6, 0, 0, 0, 7, 0))->getAbsoluteX()
        );
        static::assertSame(
            42.0,
            (new TransformationMatrix(6, 10, 10, 10, 7, 10))->getAbsoluteX()
        );
    }
}
