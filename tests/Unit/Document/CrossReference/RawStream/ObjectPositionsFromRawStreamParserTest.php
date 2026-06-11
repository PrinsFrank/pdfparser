<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CrossReference\RawStream;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CrossReference\RawStream\ObjectPositionsFromRawStreamParser;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(ObjectPositionsFromRawStreamParser::class)]
class ObjectPositionsFromRawStreamParserTest extends TestCase {
    public function testParse(): void {
        static::assertSame(
            [
                10 => 1,
                42 => 1232131,
            ],
            ObjectPositionsFromRawStreamParser::parse(
                new InMemoryStream(
                    <<<PDF
                    %%PDF-1.7
                    1 0 obj
                    4 0 4 0 testobj
                    endobj

                    1232131 0 obj
                    endobj

                    PDF
                )
            )
        );
    }
}
