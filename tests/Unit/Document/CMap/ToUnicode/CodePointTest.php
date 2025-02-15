<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\ToUnicode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\CodePoint;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

#[CoversClass(CodePoint::class)]
class CodePointTest extends TestCase {
    public function testToString(): void {
        static::assertSame('f', CodePoint::toString('0066'));
        static::assertSame('i', CodePoint::toString('0069'));
        static::assertSame('fi', CodePoint::toString('00660069'));
        static::assertSame('𝄞', CodePoint::toString('D834DD1E'));
        static::assertSame('🇳🇱', CodePoint::toString('D83CDDF3D83CDDF1'));
    }

    public function testToStringThrowsExceptionOnNonHexString(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected hex string, got "Z"');
        CodePoint::toString('Z');
    }
}
