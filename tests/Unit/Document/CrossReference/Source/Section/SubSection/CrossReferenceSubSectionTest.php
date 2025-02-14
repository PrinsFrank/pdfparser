<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference\Source\Section\SubSection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(CrossReferenceSubSection::class)]
class CrossReferenceSubSectionTest extends TestCase {
    public function testConstructThrowsExceptionWithNegativeNumberOfEntries(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$nrOfEntries should be a positive number');
        new CrossReferenceSubSection(42, -1);
    }

    public function testContainsObject(): void {
        $subSection = new CrossReferenceSubSection(0, 0);
        static::assertFalse($subSection->containsObject(0));
        static::assertFalse($subSection->containsObject(1));

        $subSection = new CrossReferenceSubSection(0, 1);
        static::assertTrue($subSection->containsObject(0));
        static::assertFalse($subSection->containsObject(1));

        $subSection = new CrossReferenceSubSection(42, 1);
        static::assertTrue($subSection->containsObject(42));
        static::assertFalse($subSection->containsObject(43));
    }

    public function testGetCrossReferenceEntry(): void {
        $subSection = new CrossReferenceSubSection(
            42,
            2,
            $firstEntry = new CrossReferenceEntryInUseObject(0, 0),
            $secondEntry = new CrossReferenceEntryInUseObject(0, 0),
        );
        static::assertSame($firstEntry, $subSection->getCrossReferenceEntry(42));
        static::assertSame($secondEntry, $subSection->getCrossReferenceEntry(43));

        $subSection = new CrossReferenceSubSection(
            42,
            1,
            $firstEntry = new CrossReferenceEntryInUseObject(0, 0),
            new CrossReferenceEntryInUseObject(0, 0),
        );
        static::assertSame($firstEntry, $subSection->getCrossReferenceEntry(42));
        static::assertNull($subSection->getCrossReferenceEntry(43));
    }

    public function testGetCrossReferenceEntryThrowsExceptionWhenObjectDoesntExist(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Object with key 0 not found');

        (new CrossReferenceSubSection(42, 1))
            ->getCrossReferenceEntry(42);
    }

    public function testGetGrossReferenceEntryThrowsExceptionWhenFreeObject(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cross reference entry for object should point to either a compressed or uncompressed entry, not a free object nr');

        (new CrossReferenceSubSection(42, 1, new CrossReferenceEntryFreeObject(0, 0)))
            ->getCrossReferenceEntry(42);
    }
}
