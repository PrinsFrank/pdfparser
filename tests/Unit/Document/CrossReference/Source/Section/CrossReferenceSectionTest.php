<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference\Source\Section;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;

#[CoversClass(CrossReferenceSection::class)]
class CrossReferenceSectionTest extends TestCase {
    public function testGetCrossReferenceEntry(): void {
        static::assertNull((new CrossReferenceSection(new Dictionary()))->getCrossReferenceEntry(42));

        $crossReferenceSection = new CrossReferenceSection(
            new Dictionary(),
            new CrossReferenceSubSection(
                42,
                2,
                $crossReferenceEntry1 = new CrossReferenceEntryInUseObject(0, 0),
                $crossReferenceEntry2 = new CrossReferenceEntryInUseObject(0, 0),
            ),
        );
        static::assertSame($crossReferenceEntry1, $crossReferenceSection->getCrossReferenceEntry(42));
        static::assertSame($crossReferenceEntry2, $crossReferenceSection->getCrossReferenceEntry(43));

        $crossReferenceSection = new CrossReferenceSection(
            new Dictionary(),
            new CrossReferenceSubSection(
                42,
                2,
                new CrossReferenceEntryInUseObject(0, 0),
                $crossReferenceEntry1 = new CrossReferenceEntryInUseObject(0, 0),
            ),
            new CrossReferenceSubSection(
                2,
                2,
                new CrossReferenceEntryInUseObject(0, 0),
                $crossReferenceEntry2 = new CrossReferenceEntryInUseObject(0, 0),
            ),
        );
        static::assertSame($crossReferenceEntry1, $crossReferenceSection->getCrossReferenceEntry(43));
        static::assertSame($crossReferenceEntry2, $crossReferenceSection->getCrossReferenceEntry(3));
    }
}
