<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text\OperatorString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(TextShowingOperator::class)]
class TextShowingOperatorTest extends TestCase {
    public function testDisplayOperands(): void {
        static::assertSame(PHP_EOL, TextShowingOperator::MOVE_SHOW->displayOperands('', null));
        static::assertSame(PHP_EOL, TextShowingOperator::MOVE_SHOW_SPACING->displayOperands('', null));
        static::assertSame('foo', TextShowingOperator::SHOW->displayOperands('(foo)', null));
        static::assertSame('foo ', TextShowingOperator::SHOW->displayOperands('(foo)-30', null));
    }

    public function testDisplayThrowsExceptionWhenInvalidFormat(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Operator SHOW with operands "foo" is not in a recognized format');
        TextShowingOperator::SHOW->displayOperands('foo', null);
    }

    public function testDisplayOperandsWithoutFontForEncodedChars(): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('No font available');
        static::assertSame('foo', TextShowingOperator::SHOW->displayOperands('<0000>', null));
    }
}
