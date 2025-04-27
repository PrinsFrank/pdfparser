<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text\OperatorString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

#[CoversClass(TextPositioningOperator::class)]
class TextPositioningOperatorTest extends TestCase {
    public function testDisplay(): void {
        static::assertSame("\n", TextPositioningOperator::NEXT_LINE->display(''));
        static::assertSame("\n", TextPositioningOperator::NEXT_LINE->display('foo'));
        static::assertSame('', TextPositioningOperator::MOVE_OFFSET->display('0 0'));
        static::assertSame(' ', TextPositioningOperator::MOVE_OFFSET->display('-30 0'));
        static::assertSame("\n", TextPositioningOperator::MOVE_OFFSET->display('0 -30'));
        static::assertSame("\n", TextPositioningOperator::MOVE_OFFSET->display('-30 -30'));
        static::assertSame('', TextPositioningOperator::MOVE_OFFSET_LEADING->display(''));
        static::assertSame('', TextPositioningOperator::MOVE_OFFSET_LEADING->display('foo'));
        static::assertSame('', TextPositioningOperator::SET_MATRIX->display(''));
        static::assertSame('', TextPositioningOperator::SET_MATRIX->display('foo'));
    }

    public function testDisplayThrowsExceptionWithInvalidMoveOffsetArgumentCount(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operand, expected 2 offsets, got 1 in "0"');
        TextPositioningOperator::MOVE_OFFSET->display('0');
    }
}
