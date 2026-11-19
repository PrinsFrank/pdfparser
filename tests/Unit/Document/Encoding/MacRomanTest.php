<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Encoding\MacRoman;

#[CoversClass(MacRoman::class)]
class MacRomanTest extends TestCase {
    public function testTextToUnicodeDoesntConvertCharactersAlreadyInCorrectEncoding(): void {
        static::assertSame('ö', MacRoman::textToUnicode('ö'));
    }
}
