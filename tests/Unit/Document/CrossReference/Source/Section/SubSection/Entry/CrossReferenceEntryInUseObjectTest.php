<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference\Source\Section\SubSection\Entry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;

#[CoversClass(CrossReferenceEntryInUseObject::class)]
class CrossReferenceEntryInUseObjectTest extends TestCase {
    public function testConstruct(): void {
        $entry = new CrossReferenceEntryInUseObject(42, 43);
        static::assertSame(42, $entry->byteOffsetInDecodedStream);
        static::assertSame(43, $entry->generationNumber);
    }
}
