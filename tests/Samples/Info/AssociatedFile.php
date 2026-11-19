<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

use DateTimeImmutable;

readonly class AssociatedFile {
    public function __construct(
        public ?string $fileSpecificationString,
        public ?int $length,
        public ?int $size,
        public ?DateTimeImmutable $creationDate,
        public ?DateTimeImmutable $modificationDate,
        public ?string $path,
    ) {}
}
