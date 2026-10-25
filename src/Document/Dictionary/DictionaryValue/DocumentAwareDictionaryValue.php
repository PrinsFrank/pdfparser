<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue;

use Override;
use PrinsFrank\PdfParser\Document\Document;

interface DocumentAwareDictionaryValue extends DictionaryValue {
    #[Override]
    public static function fromValue(string $valueString, ?Document $document = null): ?self;
}
