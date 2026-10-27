<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Catalog;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\GenericDictionary;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class H3SimpleStringTest extends TestCase {
    public function testStructure(): void {
        $document = (new PdfParser())
            ->parseFile(__DIR__ . '/samples/h3-simple-string.pdf');

        static::assertSame(Version::V1_4, $document->version);
        static::assertEquals(
            new CrossReferenceSource(
                new CrossReferenceSection(
                    new Dictionary(
                        null,
                        new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(8)),
                        new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(1, 0)),
                    ),
                    new CrossReferenceSubSection(
                        0,
                        8,
                        new CrossReferenceEntryFreeObject(0, 65535),
                        new CrossReferenceEntryInUseObject(9, 0),
                        new CrossReferenceEntryInUseObject(74, 0),
                        new CrossReferenceEntryInUseObject(120, 0),
                        new CrossReferenceEntryInUseObject(179, 0),
                        new CrossReferenceEntryInUseObject(322, 0),
                        new CrossReferenceEntryInUseObject(417, 0),
                        new CrossReferenceEntryInUseObject(447, 0),
                    ),
                ),
            ),
            $document->crossReferenceSource,
        );
        $obj1 = $document->getObject(1);
        static::assertEquals(
            new UncompressedObject(
                $document,
                1,
                0,
                9,
                73,
            ),
            $obj1,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj1,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
                    new DictionaryEntry(DictionaryKey::OUTLINES, new ReferenceValue(2, 0)),
                    new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
                ),
            ),
            $obj1?->getDictionary(),
        );
        $obj2 = $document->getObject(2);
        static::assertEquals(
            new UncompressedObject(
                $document,
                2,
                0,
                74,
                119,
            ),
            $obj2,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj2,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::OUTLINES),
                    new DictionaryEntry(DictionaryKey::COUNT, new IntegerValue(0)),
                ),
            ),
            $obj2?->getDictionary(),
        );
        $obj3 = $document->getObject(3);
        static::assertEquals(
            new UncompressedObject(
                $document,
                3,
                0,
                120,
                178,
            ),
            $obj3,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj3,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::PAGES),
                    new DictionaryEntry(DictionaryKey::KIDS, new ReferenceValueArray(new ReferenceValue(4, 0))),
                    new DictionaryEntry(DictionaryKey::COUNT, new IntegerValue(1)),
                ),
            ),
            $obj3?->getDictionary(),
        );
        $obj4 = $document->getObject(4);
        static::assertEquals(
            new UncompressedObject(
                $document,
                4,
                0,
                179,
                321,
            ),
            $obj4,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj4,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::PAGE),
                    new DictionaryEntry(DictionaryKey::PARENT, new ReferenceValue(3, 0)),
                    new DictionaryEntry(DictionaryKey::MEDIA_BOX, new Rectangle(0.0, 0.0, 612.0, 792.0)),
                    new DictionaryEntry(DictionaryKey::CONTENTS, new ReferenceValue(5, 0)),
                    new DictionaryEntry(DictionaryKey::RESOURCES, new Dictionary(
                        $obj4,
                        new DictionaryEntry(DictionaryKey::PROC_SET, new ReferenceValue(6, 0)),
                        new DictionaryEntry(
                            DictionaryKey::FONT,
                            new Dictionary(
                                $obj4,
                                new DictionaryEntry(new NameObjectStringValue('F1'), new ReferenceValue(7, 0)),
                            ),
                        ),
                    )),
                ),
            ),
            $obj4?->getDictionary(),
        );
        $obj5 = $document->getObject(5);
        static::assertEquals(
            new UncompressedObject(
                $document,
                5,
                0,
                322,
                416,
            ),
            $obj5,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj5,
                    new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(46)),
                ),
            ),
            $obj5?->getDictionary(),
        );
        $obj6 = $document->getObject(6);
        static::assertEquals(
            new UncompressedObject(
                $document,
                6,
                0,
                417,
                446,
            ),
            $obj6,
        );
        static::assertEquals(
            new GenericDictionary(new Dictionary($obj6)),
            $obj6?->getDictionary(),
        );
        $obj7 = $document->getObject(7);
        static::assertEquals(
            new UncompressedObject(
                $document,
                7,
                0,
                447,
                554,
            ),
            $obj7,
        );
        static::assertEquals(
            new GenericDictionary(
                new Dictionary(
                    $obj7,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::FONT),
                    new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::TYPE_1),
                    new DictionaryEntry(DictionaryKey::NAME, new NameObjectStringValue('F1')),
                    new DictionaryEntry(DictionaryKey::BASE_FONT, new NameObjectStringValue('Helvetica')),
                    new DictionaryEntry(DictionaryKey::ENCODING, EncodingNameValue::MacRomanEncoding),
                ),
            ),
            $obj7?->getDictionary(),
        );
        static::assertEquals(
            new Catalog(
                new Dictionary(
                    $obj1,
                    new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
                    new DictionaryEntry(DictionaryKey::OUTLINES, new ReferenceValue(2, 0)),
                    new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
                ),
            ),
            $document->getCatalog(),
        );
        static::assertEquals(null, $document->getInformationDictionary());
    }
}
