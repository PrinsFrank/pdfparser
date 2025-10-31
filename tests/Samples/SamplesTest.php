<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Version\Version;
use PrinsFrank\PdfParser\PdfParser;
use PrinsFrank\PdfParser\Tests\Samples\Info\FileInfo;
use PrinsFrank\PdfParser\Tests\Samples\Info\SampleProvider;
use RuntimeException;
use TypeError;
use ValueError;

#[CoversNothing]
class SamplesTest extends TestCase {
    /** @throws TypeError|ValueError|RuntimeException */
    #[DataProviderExternal(SampleProvider::class, 'samples')]
    public function testExternalSourcePDFs(FileInfo $fileInfo): void {
        $document = (new PdfParser())->parseFile($fileInfo->pdfPath);

        // PDF version
        static::assertSame(Version::from(number_format($fileInfo->version / 10, 1)), $document->version);

        // title
        if (null !== $fileInfo->title) {
            static::assertSame($fileInfo->title, $document->getInformationDictionary()?->getTitle());
        }

        // producer
        if (null !== $fileInfo->producer) {
            static::assertSame($fileInfo->producer, $document->getInformationDictionary()?->getProducer());
        }

        // author
        if (null !== $fileInfo->author) {
            static::assertSame($fileInfo->author, $document->getInformationDictionary()?->getAuthor());
        }

        // creator
        if (null !== $fileInfo->creator) {
            static::assertSame($fileInfo->creator, $document->getInformationDictionary()?->getCreator());
        }

        // creation date
        if (null !== $fileInfo->creationDate) {
            static::assertEquals($fileInfo->creationDate, $document->getInformationDictionary()?->getCreationDate());
        }

        // modification date
        if (null !== $fileInfo->modificationDate) {
            static::assertEquals($fileInfo->modificationDate, $document->getInformationDictionary()?->getModificationDate());
        }

        /*
         * Check content related expectations
         */
        if (is_array($fileInfo->textPartsExpectedSomewhereInTheExtractedText)) {
            foreach ($fileInfo->textPartsExpectedSomewhereInTheExtractedText as $textPart) {
                static::assertStringContainsString($textPart, $document->getText());
            }
        }

        /*
         * Check content of pages (fulltext)
         */
        $pages = $fileInfo->pages ?? [];
        static::assertSame(count($pages), $document->getNumberOfPages());

        foreach ($pages as $index => $expectedPage) {
            static::assertNotNull($page = $document->getPage($index + 1));
            static::assertSame(trim($expectedPage->content), trim($page->getText()));
            foreach ($expectedPage->imagePaths as $imageIndex => $imagePath) {
                self::assertImage(
                    $imagePath,
                    $page->getImages()[$imageIndex]->getStream()->toString(),
                    sprintf('Page %d, image %d', $index, $imageIndex),
                );
            }
        }
    }

    /** @throws RuntimeException */
    private static function assertImage(string $expectedImagePath, string $actualImageContent, string $imageName): void {
        $expectedImageContent = file_get_contents($expectedImagePath);
        if ($expectedImageContent === false) {
            throw new RuntimeException(sprintf('Unable to load expected image "%s"', $expectedImagePath));
        }

        if (str_ends_with($expectedImagePath, '.tiff')) { // gd doesn't have support for tiff so we have to compare the raw content
            static::assertSame(
                $expectedImageContent,
                $actualImageContent,
                $imageName,
            );

            return;
        }

        if (($expectedImage = imagecreatefromstring($expectedImageContent)) === false) {
            throw new RuntimeException(sprintf('Unable to load expected image "%s"', $imageName));
        }

        if (($actualImage = imagecreatefromstring($actualImageContent)) === false) {
            throw new RuntimeException(sprintf('Unable to load actual image "%s"', $imageName));
        }

        static::assertSame(imagesx($expectedImage), imagesx($actualImage), $imageName);
        static::assertSame(imagesy($expectedImage), imagesy($actualImage), $imageName);
        for ($x = 0; $x < imagesx($expectedImage); $x++) {
            for ($y = 0; $y < imagesy($expectedImage); $y++) {
                static::assertSame(
                    imagecolorat($expectedImage, $x, $y),
                    imagecolorat($actualImage, $x, $y),
                    $imageName,
                );
            }
        }
    }
}
