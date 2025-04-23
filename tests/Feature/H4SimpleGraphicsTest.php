<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Object\Decorator\Catalog;
use PrinsFrank\PdfParser\Document\Object\Decorator\GenericObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Decorator\Pages;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class H4SimpleGraphicsTest extends TestCase {
    public function testStructure(): void {
        $document = (new PdfParser())
            ->parseFile(__DIR__ . '/samples/h4-simple-graphics.pdf');

        static::assertSame(Version::V1_4, $document->version);
        static::assertEquals(
            new CrossReferenceSource(
                new CrossReferenceSection(
                    new Dictionary(
                        new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(7)),
                        new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(1, 0)),
                    ),
                    new CrossReferenceSubSection(
                        0,
                        7,
                        new CrossReferenceEntryFreeObject(0, 65535),
                        new CrossReferenceEntryInUseObject(9, 0),
                        new CrossReferenceEntryInUseObject(74, 0),
                        new CrossReferenceEntryInUseObject(120, 0),
                        new CrossReferenceEntryInUseObject(179, 0),
                        new CrossReferenceEntryInUseObject(300, 0),
                        new CrossReferenceEntryInUseObject(952, 0),
                    ),
                ),
            ),
            $document->crossReferenceSource,
        );
        $obj1 = $document->getObject(1);
        static::assertEquals(
            new Catalog(
                new UncompressedObject(
                    1,
                    0,
                    9,
                    73,
                ),
                $document,
            ),
            $obj1,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::CATALOG),
                new DictionaryEntry(DictionaryKey::OUTLINES, new ReferenceValue(2, 0)),
                new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
            ),
            $obj1?->getDictionary()
        );
        $obj2 = $document->getObject(2);
        static::assertEquals(
            new GenericObject(
                new UncompressedObject(
                    2,
                    0,
                    74,
                    119,
                ),
                $document,
            ),
            $obj2,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::OUTLINES),
                new DictionaryEntry(DictionaryKey::COUNT, new IntegerValue(0)),
            ),
            $obj2?->getDictionary()
        );
        $obj3 = $document->getObject(3);
        static::assertEquals(
            new Pages(
                new UncompressedObject(
                    3,
                    0,
                    120,
                    178,
                ),
                $document,
            ),
            $obj3,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::PAGES),
                new DictionaryEntry(DictionaryKey::KIDS, new ReferenceValueArray(new ReferenceValue(4, 0))),
                new DictionaryEntry(DictionaryKey::COUNT, new IntegerValue(1)),
            ),
            $obj3?->getDictionary()
        );
        $obj4 = $document->getObject(4);
        static::assertEquals(
            new Page(
                new UncompressedObject(
                    4,
                    0,
                    179,
                    299,
                ),
                $document,
            ),
            $obj4,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::TYPE, TypeNameValue::PAGE),
                new DictionaryEntry(DictionaryKey::PARENT, new ReferenceValue(3, 0)),
                new DictionaryEntry(DictionaryKey::MEDIA_BOX, new Rectangle(0.0, 0.0, 612.0, 792.0)),
                new DictionaryEntry(DictionaryKey::CONTENTS, new ReferenceValue(5, 0)),
                new DictionaryEntry(DictionaryKey::RESOURCES, new Dictionary(
                    new DictionaryEntry(DictionaryKey::PROC_SET, new ReferenceValue(6, 0)),
                )),
            ),
            $obj4?->getDictionary()
        );
        $obj5 = $document->getObject(5);
        static::assertEquals(
            new GenericObject(
                new UncompressedObject(
                    5,
                    0,
                    300,
                    951,
                ),
                $document,
            ),
            $obj5,
        );
        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(883)),
            ),
            $obj5?->getDictionary()
        );
        $obj6 = $document->getObject(6);
        static::assertEquals(
            new GenericObject(
                new UncompressedObject(
                    6,
                    0,
                    952,
                    975,
                ),
                $document
            ),
            $obj6,
        );
        static::assertEquals(
            new Dictionary(),
            $obj6?->getDictionary(),
        );
        static::assertEquals(
            new Catalog(
                new UncompressedObject(
                    1,
                    0,
                    9,
                    73,
                ),
                $document,
            ),
            $document->getCatalog()
        );
        static::assertEquals(null, $document->getInformationDictionary());
    }
}
