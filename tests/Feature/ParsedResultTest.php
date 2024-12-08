<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class ParsedResultTest extends TestCase {
    private const SAMPLES_SOURCE = '/vendor/prinsfrank/pdf-samples/';

    /** @param list<array{content: string}> $expectedPages */
    #[DataProvider('externalSamples')]
    public function testExternalSourcePDFs(string $pdfPath, ?string $password, Version $version, array $expectedPages): void {
        $parser = new PdfParser();

        $document = $parser->parse(Stream::openFile($pdfPath));
        static::assertSame($version, $document->version);
        static::assertSame(count($expectedPages), $document->getNumberOfPages());
        foreach ($expectedPages as $index => $expectedPage) {
            static::assertNotNull($page = $document->getPage($index + 1));
        }
    }

    /** @return iterable<string, array{0: string, 1: ?string, 2: Version, 3: list<array{content: string}>}> */
    public static function externalSamples(): iterable {
        $fileInfoContent = file_get_contents(dirname(__DIR__, 2) . self::SAMPLES_SOURCE . 'files.json');
        if ($fileInfoContent === false) {
            throw new RuntimeException('Unable to load file information from samples source. Should a \'composer install\' be run?');
        }

        /** @var list<object{filename: string, password: ?string, version: string, pages: list<array{content: string}>}> $fileInfoArray */
        $fileInfoArray = json_decode($fileInfoContent, flags: JSON_THROW_ON_ERROR);
        foreach ($fileInfoArray as $fileInfo) {
            if ($fileInfo->password !== null) {
                continue;
            }

            yield $fileInfo->filename => [
                dirname(__DIR__, 2) . self::SAMPLES_SOURCE . 'files/' . $fileInfo->filename,
                $fileInfo->password,
                Version::from($fileInfo->version),
                $fileInfo->pages,
            ];
        }
    }
}
