<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Object\Item\CompressedObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(CompressedObject::class)]
class CompressedObjectTest extends TestCase {
    public function testGetDictionary(): void {
        $nestedObjectContent = '12 0 << /Pages 3 0 R >>';

        $totalStream = new InMemoryStream(
            "11 0 obj\n"
            . "<< /First 4 >>\n"
            . "stream\n"
            . $nestedObjectContent . "\n"
            . "endstream\n"
            . "endobj",
        );

        $document = new Document($totalStream, Version::V1_0, new CrossReferenceSource(), null);
        $compressedObject = new CompressedObject(
            12,
            new UncompressedObject(
                $document,
                11,
                0,
                0,
                $totalStream->getSizeInBytes(),
            ),
            0,
            strlen($nestedObjectContent),
        );

        static::assertEquals(
            new Dictionary(
                new DictionaryEntry(DictionaryKey::PAGES, new ReferenceValue(3, 0)),
            ),
            $compressedObject->getDictionary($document),
        );
    }
}
