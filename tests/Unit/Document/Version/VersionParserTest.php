<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Version;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Document\Version\VersionParser;
use PrinsFrank\PdfParser\Exception\UnsupportedFileFormatException;
use PrinsFrank\PdfParser\Exception\UnsupportedPdfVersionException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(VersionParser::class)]
class VersionParserTest extends TestCase {
    public function testParseThrowsExceptionWhenNoVersionMarker(): void {
        $this->expectException(UnsupportedFileFormatException::class);
        VersionParser::parse(
            new InMemoryStream(
                'FOO'
            )
        );
    }

    public function testParseThrowsExceptionWhenInvalidVersion(): void {
        $this->expectException(UnsupportedPdfVersionException::class);
        $this->expectExceptionMessage('9.9');
        VersionParser::parse(
            new InMemoryStream(
                '%PDF-9.9'
            )
        );
    }

    public function testParse(): void {
        static::assertSame(
            Version::V1_0,
            VersionParser::parse(new InMemoryStream('%PDF-1.0'))
        );
    }
}
