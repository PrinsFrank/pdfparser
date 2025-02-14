<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference\Source;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;

#[CoversClass(CrossReferenceSource::class)]
class CrossReferenceSourceTest extends TestCase {
    public function testGetCrossReferenceEntry(): void {
        $crossReferenceSource = new CrossReferenceSource(
            new CrossReferenceSection(
                new Dictionary(),
                new CrossReferenceSubSection(
                    42,
                    1,
                    $crossReferenceEntry1 = new CrossReferenceEntryInUseObject(0, 0),
                )
            ),
            new CrossReferenceSection(
                new Dictionary(),
                new CrossReferenceSubSection(
                    43,
                    1,
                    $crossReferenceEntry2 = new CrossReferenceEntryInUseObject(0, 0),
                )
            )
        );
        static::assertSame($crossReferenceEntry1, $crossReferenceSource->getCrossReferenceEntry(42));
        static::assertSame($crossReferenceEntry2, $crossReferenceSource->getCrossReferenceEntry(43));
        static::assertNull($crossReferenceSource->getCrossReferenceEntry(44));
    }

    public function testGetReferenceForKey(): void {
        $crossReferenceSource = new CrossReferenceSource(
            new CrossReferenceSection(
                new Dictionary(new DictionaryEntry(DictionaryKey::ROOT, $referenceValue1 = new ReferenceValue(1, 0))),
                new CrossReferenceSubSection(42, 1)
            ),
            new CrossReferenceSection(
                new Dictionary(new DictionaryEntry(DictionaryKey::INFO, $referenceValue2 = new ReferenceValue(12, 0))),
                new CrossReferenceSubSection(43, 1)
            )
        );
        static::assertSame($referenceValue1, $crossReferenceSource->getReferenceForKey(DictionaryKey::ROOT));
        static::assertSame($referenceValue2, $crossReferenceSource->getReferenceForKey(DictionaryKey::INFO));
        static::assertNull($crossReferenceSource->getReferenceForKey(DictionaryKey::A));
    }
}
