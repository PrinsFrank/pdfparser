<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\PdfParser;
use PrinsFrank\PdfSamples\FileInfo;
use PrinsFrank\PdfSamples\SampleProvider;
use TypeError;
use ValueError;

#[CoversNothing]
class SamplesTest extends TestCase {
    /** @throws TypeError|ValueError */
    #[DataProviderExternal(SampleProvider::class, 'samples')]
    public function testExternalSourcePDFs(FileInfo $fileInfo): void {
        $document = (new PdfParser())->parseFile($fileInfo->pdfPath);
        static::assertSame(Version::from(number_format($fileInfo->version / 10, 1)), $document->version);
        static::assertSame($fileInfo->title, $document->getInformationDictionary()?->getTitle());
        static::assertSame($fileInfo->producer, $document->getInformationDictionary()?->getProducer());
        static::assertSame($fileInfo->author, $document->getInformationDictionary()?->getAuthor());
        static::assertSame($fileInfo->creator, $document->getInformationDictionary()?->getCreator());
        static::assertEquals($fileInfo->creationDate, $document->getInformationDictionary()?->getCreationDate());
        static::assertEquals($fileInfo->modificationDate, $document->getInformationDictionary()?->getModificationDate());
        static::assertSame(count($fileInfo->pages ?? []), $document->getNumberOfPages());
        foreach ($fileInfo->pages ?? [] as $index => $expectedPage) {
            static::assertNotNull($page = $document->getPage($index + 1));
            static::assertSame($expectedPage->content, $page->getText());
            foreach ($expectedPage->imagePaths as $imageIndex => $imagePath) {
                static::assertSame(
                    file_get_contents($imagePath),
                    $page->getImages()[$imageIndex]->getStream()->toString(),
                );
            }
        }
    }
}
