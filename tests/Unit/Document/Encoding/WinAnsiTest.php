<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Encoding\WinAnsi;

#[CoversClass(WinAnsi::class)]
class WinAnsiTest extends TestCase {
    public function testToUnicode(): void {
        $string = mb_convert_encoding('transakèní', 'Windows-1252');
        static::assertNotFalse($string);
        static::assertSame('transakèní', WinAnsi::textToUnicode($string));
    }

    public function testToUnicodeReturnsValueWhenCorrectlyEncoded(): void {
        static::assertSame('foo', WinAnsi::textToUnicode('foo'));
        static::assertSame('æöü', WinAnsi::textToUnicode('æöü'));
    }
}
