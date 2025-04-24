<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(DictionaryArrayValue::class)]
class DictionaryArrayValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(DictionaryArrayValue::fromValue(''));
        static::assertNull(DictionaryArrayValue::fromValue('[]'));
        static::assertEquals(
            new DictionaryArrayValue([new Dictionary()]),
            DictionaryArrayValue::fromValue('[<<>>]')
        );
        static::assertEquals(
            new DictionaryArrayValue([new Dictionary()]),
            DictionaryArrayValue::fromValue('[ <<>> ]')
        );
        static::assertEquals(
            new DictionaryArrayValue([new Dictionary(), new Dictionary()]),
            DictionaryArrayValue::fromValue('[<<>> <<>>]')
        );
        static::assertEquals(
            new DictionaryArrayValue([new Dictionary(new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(106))), new Dictionary(new DictionaryEntry(DictionaryKey::TITLE, new TextStringValue('(Foo)')))]),
            DictionaryArrayValue::fromValue('[<</Length 106>> <</Title(Foo)>>]')
        );
        static::assertEquals(
            new DictionaryArrayValue(
                [
                    new Dictionary(
                        new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::OUTPUT_INTENT),
                        new DictionaryEntry(DictionaryKey::S, new TextStringValue('/GTS_PDFA1')),
                        new DictionaryEntry(new ExtendedDictionaryKey('OutputConditionIdentifier'), new TextStringValue('(sRGB)')),
                        new DictionaryEntry(new ExtendedDictionaryKey('RegistryName'), new TextStringValue('(http://www.color.org)')),
                        new DictionaryEntry(DictionaryKey::INFO, new TextStringValue('(Creator: HP     Manufacturer:IEC    Model:sRGB)')),
                        new DictionaryEntry(new ExtendedDictionaryKey('DestOutputProfile'), new ReferenceValue(361, 0) ),
                    )
                ]
            ),
            DictionaryArrayValue::fromValue('[<</Type/OutputIntent/S/GTS_PDFA1/OutputConditionIdentifier(sRGB) /RegistryName(http://www.color.org) /Info(Creator: HP     Manufacturer:IEC    Model:sRGB) /DestOutputProfile 361 0 R>>]')
        );
    }
}
