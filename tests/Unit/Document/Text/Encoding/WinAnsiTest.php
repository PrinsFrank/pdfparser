<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Text\Encoding\WinAnsi;

#[CoversClass(WinAnsi::class)]
class WinAnsiTest extends TestCase {
    public function testToUnicode(): void {
        $string = mb_convert_encoding('transakèní', 'Windows-1252');
        static::assertEquals('transakèní', WinAnsi::textToUnicode($string));
    }

    public function testToUnicodeReturnsValueWhenCorrectlyEncoded(): void {
        static::assertEquals('foo', WinAnsi::textToUnicode('foo'));
        static::assertEquals('æöü', WinAnsi::textToUnicode('æöü'));
    }
}
