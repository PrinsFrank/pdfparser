<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

use DateTimeImmutable;

readonly class FileInfo {
    /** @param list<Page> $pages */
    public function __construct(
        public string $pdfPath,
        public int $version,
        public ?string $userPassword,
        public ?string $ownerPassword,
        public ?string $fileEncryptionKey,
        public ?string $title,
        public ?string $producer,
        public ?string $author,
        public ?string $creator,
        public ?DateTimeImmutable $creationDate,
        public ?DateTimeImmutable $modificationDate,
        public ?array $pages,
    ) {}
}
