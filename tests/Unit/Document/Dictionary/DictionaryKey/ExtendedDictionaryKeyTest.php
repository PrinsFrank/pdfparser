<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryKey;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(ExtendedDictionaryKey::class)]
class ExtendedDictionaryKeyTest extends TestCase {
    public function testFromKeyString(): void {
        static::assertEquals(
            new ExtendedDictionaryKey(''),
            ExtendedDictionaryKey::fromKeyString(''),
        );
        static::assertEquals(
            new ExtendedDictionaryKey('Foo'),
            ExtendedDictionaryKey::fromKeyString('/Foo'),
        );
        static::assertEquals(
            new ExtendedDictionaryKey('Foo'),
            ExtendedDictionaryKey::fromKeyString('/Foo  '),
        );
        static::assertEquals(
            new ExtendedDictionaryKey('Foo'),
            ExtendedDictionaryKey::fromKeyString('/Foo '),
        );
    }

    public function testGetValueTypes(): void {
        static::assertSame(
            [ReferenceValue::class, TextStringValue::class, Dictionary::class],
            ExtendedDictionaryKey::fromKeyString('Foo')->getValueTypes(),
        );
    }
}
