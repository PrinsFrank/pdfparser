<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class SampleProvider {
    /** @return iterable<string, FileInfo> */
    public static function samples(): iterable {
        foreach (array_diff(scandir($filesDir = dirname(__DIR__) . '/files'), ['..', '.']) as $sampleName) {
            $sampleFolder = $filesDir . DIRECTORY_SEPARATOR . $sampleName;
            if (!file_exists($pdfPath = $sampleFolder . DIRECTORY_SEPARATOR . 'file.pdf')
                || !file_exists($contentsPath = $sampleFolder . DIRECTORY_SEPARATOR . 'contents.yml')) {
                throw new RuntimeException();
            }

            $content = Yaml::parseFile($contentsPath, Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_DATETIME);
            if ($content->password !== null) {
                continue;
            }

            yield $sampleName => [
                new FileInfo(
                    $pdfPath,
                    (int) ($content->version * 10),
                    $content->password,
                    $content->title,
                    $content->producer,
                    $content->author,
                    $content->creator,
                    $content->creationDate,
                    $content->modificationDate,
                    array_map(
                        fn (object $page) => new Page(
                            $page->content,
                            array_map(fn (string $relativePath) => sprintf('%s/images/%s', $sampleFolder, $relativePath), $page->images ?? [])
                        ),
                        $content->pages
                    ),
                )
            ];
        }
    }
}
