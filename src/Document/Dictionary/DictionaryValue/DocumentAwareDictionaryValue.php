<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue;

use Override;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;

interface DocumentAwareDictionaryValue extends DictionaryValue {
    #[Override]
    public static function fromValue(string $valueString, UncompressedObject|CompressedObject|null $object = null): ?self;
}
