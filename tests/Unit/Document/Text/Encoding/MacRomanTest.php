<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Text\Encoding\MacRoman;

#[CoversClass(MacRoman::class)]
class MacRomanTest extends TestCase {
    public function testTextToUnicode(): void {
        static::assertSame('√û', MacRoman::textToUnicode('Þ'));
    }
}
