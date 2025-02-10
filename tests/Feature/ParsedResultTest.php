<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use DateTimeImmutable;
use Exception;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class ParsedResultTest extends TestCase {
    private const SAMPLES_SOURCE = '/vendor/prinsfrank/pdf-samples/';

    /** @param list<object{content: string}> $expectedPages */
    #[DataProvider('externalSamples')]
    public function testExternalSourcePDFs(
        string $pdfPath,
        Version $expectedVersion,
        ?string $expectedTitle,
        ?string $expectedProducer,
        ?string $expectedAuthor,
        ?string $expectedCreator,
        ?DateTimeImmutable $expectedCreationDate,
        ?DateTimeImmutable $expectedModificationDate,
        array $expectedPages
    ): void {
        $document = (new PdfParser())->parseFile($pdfPath);

        static::assertSame($expectedVersion, $document->version);
        static::assertSame($expectedTitle, $document->getInformationDictionary()?->getTitle());
        static::assertSame($expectedProducer, $document->getInformationDictionary()?->getProducer());
        static::assertSame($expectedAuthor, $document->getInformationDictionary()?->getAuthor());
        static::assertSame($expectedCreator, $document->getInformationDictionary()?->getCreator());
        static::assertEquals($expectedCreationDate, $document->getInformationDictionary()?->getCreationDate());
        static::assertEquals($expectedModificationDate, $document->getInformationDictionary()?->getModificationDate());
        static::assertSame(count($expectedPages), $document->getNumberOfPages());
        foreach ($expectedPages as $index => $expectedPage) {
            static::assertNotNull($page = $document->getPage($index + 1));
            static::assertSame($expectedPage->content, $page->getText());
        }
    }

    /**
     * @throws JsonException
     * @throws Exception
     * @return iterable<mixed>
     */
    public static function externalSamples(): iterable {
        $fileInfoContent = file_get_contents(dirname(__DIR__, 2) . self::SAMPLES_SOURCE . 'files.json');
        if ($fileInfoContent === false) {
            throw new RuntimeException('Unable to load file information from samples source. Should a \'composer install\' be run?');
        }

        /** @var list<object{filename: string, password: ?string, version: string, title: ?string, producer: ?string, author: ?string, creator: ?string, creationDate: ?string, modificationDate: ?string, pages: list<object{content: string}>}> $fileInfoArray */
        $fileInfoArray = json_decode($fileInfoContent, flags: JSON_THROW_ON_ERROR);
        foreach ($fileInfoArray as $fileInfo) {
            if ($fileInfo->password !== null) {
                continue;
            }

            yield $fileInfo->filename => [
                dirname(__DIR__, 2) . self::SAMPLES_SOURCE . 'files/' . $fileInfo->filename,
                Version::tryFrom($fileInfo->version) ?? throw new InvalidArgumentException('Invalid version'),
                $fileInfo->title,
                $fileInfo->producer,
                $fileInfo->author,
                $fileInfo->creator,
                $fileInfo->creationDate === null ? null : new DateTimeImmutable($fileInfo->creationDate),
                $fileInfo->modificationDate === null ? null : new DateTimeImmutable($fileInfo->modificationDate),
                $fileInfo->pages,
            ];
        }
    }
}
