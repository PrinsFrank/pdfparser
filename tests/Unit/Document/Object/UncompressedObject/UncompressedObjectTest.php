<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Object\UncompressedObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\Source\CrossReferenceSource;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(UncompressedObject::class)]
class UncompressedObjectTest extends TestCase {
    public function testGetContentSimpleObject(): void {
        static::assertSame(
            <<<EOD

            foo

            EOD,
            (new UncompressedObject(
                new Document(
                    $stream = new InMemoryStream(
                        <<<EOD
                        42 0 obj
                        foo
                        endobj
                        EOD,
                    ),
                    Version::V1_0,
                    new CrossReferenceSource(),
                    null,
                ),
                42,
                0,
                0,
                $stream->getSizeInBytes(),
            ))
                ->getContent()
                ->toString(),
        );
    }

    public function testGetContentObjectOnSameLine(): void {
        static::assertSame(
            <<<EOD
             0

            EOD,
            (new UncompressedObject(
                new Document(
                    $stream = new InMemoryStream(
                        <<<EOD
                        42 0 obj 0
                        endobj
                        EOD,
                    ),
                    Version::V1_0,
                    new CrossReferenceSource(),
                    null,
                ),
                42,
                0,
                0,
                $stream->getSizeInBytes(),
            ))
                ->getContent()
                ->toString(),
        );
    }
}
