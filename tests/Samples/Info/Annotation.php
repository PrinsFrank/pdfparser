<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Samples\Info;

readonly class Annotation {
    public function __construct(
        public ?string $subType,
        public ?string $linkLocation,
        public ?string $partialFieldName,
        public ?string $alternativeFieldName,
        public ?string $mappingName,
        public ?string $value,
    ) {}
}
