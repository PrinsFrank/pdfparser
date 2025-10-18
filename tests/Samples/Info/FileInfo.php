<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

use DateTimeImmutable;

class FileInfo {
    /** @param list<Page> $pages */
    public function __construct(
        public readonly string $pdfPath,
        public readonly int $version,
        public readonly ?string $password,
        public readonly ?string $title,
        public readonly ?string $producer,
        public readonly ?string $author,
        public readonly ?string $creator,
        public readonly ?DateTimeImmutable $creationDate,
        public readonly ?DateTimeImmutable $modificationDate,
        public readonly ?array $pages,
    ) {
    }
}
