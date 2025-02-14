<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(TextStringValue::class)]
class TextStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new TextStringValue('(foo)'), TextStringValue::fromValue('(foo)'));
    }
}
