<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryKey;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean\BooleanValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float\FloatValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

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
            [
                Dictionary::class,
                ArrayValue::class,
                DictionaryArrayValue::class,
                BooleanValue::class,
                DateValue::class,
                FloatValue::class,
                IntegerValue::class,
                Rectangle::class,
                ReferenceValue::class,
                ReferenceValueArray::class,
                HexadecimalStringValue::class,
                LiteralStringValue::class,
                NameObjectStringValue::class,
            ],
            ExtendedDictionaryKey::fromKeyString('Foo')->getValueTypes(),
        );
    }
}
