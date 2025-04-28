<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\PdfParser;

#[CoversClass(PdfParser::class)]
class PdfParserTest extends TestCase {
    public function testParseStringInMemory(): void {
        static::assertNotFalse($fileContent = file_get_contents(dirname(__DIR__) . '/Feature/samples/h3-simple-string.pdf'));
        static::assertSame(
            ' Hello World ',
            (new PdfParser())
                ->parseString(
                    $fileContent
                )->getText(),
        );
    }

    public function testParseStringWithFileCache(): void {
        static::assertNotFalse($fileContent = file_get_contents(dirname(__DIR__) . '/Feature/samples/h3-simple-string.pdf'));
        static::assertSame(
            ' Hello World ',
            (new PdfParser())
                ->parseString(
                    $fileContent,
                    true,
                )->getText(),
        );
    }

    public function testParseFileInMemory(): void {
        static::assertSame(
            ' Hello World ',
            (new PdfParser())
                ->parseFile(
                    dirname(__DIR__) . '/Feature/samples/h3-simple-string.pdf'
                )
                ->getText()
        );
    }

    public function testParseFileWithoutMemoryStream(): void {
        static::assertSame(
            ' Hello World ',
            (new PdfParser())
                ->parseFile(
                    dirname(__DIR__) . '/Feature/samples/h3-simple-string.pdf',
                    false
                )
                ->getText()
        );
    }

    public function testParseFileThrowsExceptionWhenUnableToOpenFile(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to open file at path "oeuoeu"');
        (new PdfParser())->parseFile('oeuoeu');
    }
}
