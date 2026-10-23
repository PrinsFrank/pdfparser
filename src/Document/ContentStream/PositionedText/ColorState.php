<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

readonly class ColorState {
    /**
     * @param list<float> $colorStrokingOperations
     * @param list<float> $colorNonStrokingOperations
     */
    public function __construct(
        public DeviceColorSpaceNameValue|NameObjectStringValue|null $colorSpaceStrokingOperations = null,
        public ?array $colorStrokingOperations = null,
        public DeviceColorSpaceNameValue|NameObjectStringValue|null $colorSpaceNonStrokingOperations = null,
        public ?array $colorNonStrokingOperations = null,
    ) {}
}
