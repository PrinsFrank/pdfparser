<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Manual;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class LocalSampleTest extends TestCase {
    #[DataProvider('samples')]
    public function testImages(string $path): void {
        $document = (new PdfParser())
            ->parseFile($path);

        foreach ($document->getImages() as $index => $image) {
            $image->getContent();
            // file_put_contents(sprintf('%s/img_%d.%s', __DIR__, $index, $image->getImageType()->getFileExtension()), $image->getContent());
        }
    }

    #[DataProvider('samples')]
    public function testGetText(string $path): void {
        $document = (new PdfParser())
            ->parseFile($path);

        var_dump($document->getText());
    }

    /** @return iterable<string, array{0: string}> */
    public static function samples(): iterable {
        $files = scandir($folder = __DIR__ . '/private-samples');
        if ($files === false) {
            throw new RuntimeException('Unable to retrieve files from private-samples directory');
        }

        foreach ($files as $filePath) {
            if (in_array($filePath, ['..', '.', '.gitignore'], true)) {
                continue;
            }

            yield $filePath => [$folder . '/' . $filePath];
        }
    }
}
