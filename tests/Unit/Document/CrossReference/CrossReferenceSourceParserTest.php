<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\CrossReferenceSourceParser;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\CrossReferenceSubSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryFreeObject;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(CrossReferenceSourceParser::class)]
class CrossReferenceSourceParserTest extends TestCase {
    public function testParse(): void {
        static::assertEquals(
            new CrossReferenceSource(
                new CrossReferenceSection(
                    new Dictionary(
                        new DictionaryEntry(DictionaryKey::SIZE, new IntegerValue(7)),
                        new DictionaryEntry(DictionaryKey::ROOT, new ReferenceValue(1, 0))
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
                        new CrossReferenceEntryInUseObject(384, 0),
                    )
                )
            ),
            CrossReferenceSourceParser::parse(
                new InMemoryStream(
                    <<<EOD
                    %PDF-1.4
                    xref
                    0 7
                    0000000000 65535 f
                    0000000009 00000 n
                    0000000074 00000 n
                    0000000120 00000 n
                    0000000179 00000 n
                    0000000300 00000 n
                    0000000384 00000 n
                    trailer
                    << /Size 7
                    /Root 1 0 R
                    >>
                    startxref
                    9
                    %%EOF
                    EOD
                )
            )
        );
    }
}
