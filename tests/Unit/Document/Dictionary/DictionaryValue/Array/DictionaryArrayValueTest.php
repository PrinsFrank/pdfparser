<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EventNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

#[CoversClass(DictionaryArrayValue::class)]
class DictionaryArrayValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(DictionaryArrayValue::fromValue(''));
        static::assertNull(DictionaryArrayValue::fromValue('[]'));
        static::assertEquals(
            new DictionaryArrayValue(),
            DictionaryArrayValue::fromValue('[null]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary()),
            DictionaryArrayValue::fromValue('[<<>>]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary()),
            DictionaryArrayValue::fromValue('[ <<>> ]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary(), new Dictionary()),
            DictionaryArrayValue::fromValue('[<<>> <<>>]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary(), new Dictionary()),
            DictionaryArrayValue::fromValue('[null <<>> <<>>]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary(), new Dictionary()),
            DictionaryArrayValue::fromValue('[<<>> <<>> null]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(new Dictionary(new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(106))), new Dictionary(new DictionaryEntry(DictionaryKey::TITLE, new LiteralStringValue('Foo')))),
            DictionaryArrayValue::fromValue('[<</Length 106>> <</Title(Foo)>>]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(
                new Dictionary(
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::OUTPUT_INTENT),
                    new DictionaryEntry(DictionaryKey::S, new NameObjectStringValue('GTS_PDFA1')),
                    new DictionaryEntry(new NameObjectStringValue('OutputConditionIdentifier'), new LiteralStringValue('sRGB')),
                    new DictionaryEntry(new NameObjectStringValue('RegistryName'), new LiteralStringValue('http://www.color.org')),
                    new DictionaryEntry(DictionaryKey::INFO, new LiteralStringValue('Creator: HP     Manufacturer:IEC    Model:sRGB')),
                    new DictionaryEntry(new NameObjectStringValue('DestOutputProfile'), new ReferenceValue(361, 0)),
                ),
            ),
            DictionaryArrayValue::fromValue('[<</Type/OutputIntent/S/GTS_PDFA1/OutputConditionIdentifier(sRGB) /RegistryName(http://www.color.org) /Info(Creator: HP     Manufacturer:IEC    Model:sRGB) /DestOutputProfile 361 0 R>>]'),
        );
        static::assertEquals(
            new DictionaryArrayValue(
                new Dictionary(
                    new DictionaryEntry(DictionaryKey::CATEGORY, new ArrayValue(['/Print'])),
                    new DictionaryEntry(DictionaryKey::EVENT, EventNameValue::Print),
                    new DictionaryEntry(DictionaryKey::OCGS, new ReferenceValueArray(new ReferenceValue(939, 0), new ReferenceValue(419, 0))),
                ),
                new Dictionary(
                    new DictionaryEntry(DictionaryKey::CATEGORY, new ArrayValue(['/View'])),
                    new DictionaryEntry(DictionaryKey::EVENT, EventNameValue::View),
                    new DictionaryEntry(DictionaryKey::OCGS, new ReferenceValueArray(new ReferenceValue(939, 0), new ReferenceValue(419, 0))),
                ),
            ),
            DictionaryArrayValue::fromValue(
                <<<DICTIONARYARRAY
                [
                    <<
                        /Category [/Print]
                        /Event/Print
                        /OCGs [939 0 R 419 0 R]
                    >>
                    <<
                        /Category [/View]
                        /Event/View
                        /OCGs[939 0 R 419 0 R]
                    >>
                ]
                DICTIONARYARRAY,
            ),
        );
    }
}
