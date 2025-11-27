<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class SampleProvider {
    /**
     * @throws RuntimeException
     * @return iterable<string, array{0: FileInfo}>
     */
    public static function samples(): iterable {
        $files = scandir($filesDir = dirname(__DIR__) . '/files');
        if ($files === false) {
            throw new RuntimeException();
        }

        foreach (array_diff($files, ['..', '.']) as $sampleName) {
            $sampleFolder = $filesDir . DIRECTORY_SEPARATOR . $sampleName;
            if (!file_exists($pdfPath = $sampleFolder . DIRECTORY_SEPARATOR . 'file.pdf')
                || !file_exists($contentsPath = $sampleFolder . DIRECTORY_SEPARATOR . 'contents.yml')) {
                throw new RuntimeException();
            }

            /** @var object{version: float, userPassword: ?string, ownerPassword: ?string, title: ?string, producer: ?string, author: ?string, creator: ?string, creationDate: ?\DateTimeImmutable, modificationDate: ?\DateTimeImmutable, pages: list<object{content: string, images?: string[]}>} $content */
            $content = Yaml::parseFile($contentsPath, Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_DATETIME);
            yield $sampleName => [
                new FileInfo(
                    $pdfPath,
                    (int) ($content->version * 10),
                    $content->userPassword,
                    $content->ownerPassword,
                    $content->title,
                    $content->producer,
                    $content->author,
                    $content->creator,
                    $content->creationDate,
                    $content->modificationDate,
                    array_map(
                        /** @param object{content: string, images?: string[]} $page */
                        fn (object $page) => new Page(
                            $page->content,
                            array_values(array_map(fn (string $relativePath) => sprintf('%s/images/%s', $sampleFolder, $relativePath), $page->images ?? []))
                        ),
                        $content->pages
                    ),
                )
            ];
        }
    }
}
