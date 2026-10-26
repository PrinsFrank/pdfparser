<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue;

use Override;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;

interface DocumentAwareDictionaryValue extends DictionaryValue {
    #[Override]
    public static function fromValue(string $valueString, UncompressedObject|CompressedObject|null $object = null): ?self;
}
