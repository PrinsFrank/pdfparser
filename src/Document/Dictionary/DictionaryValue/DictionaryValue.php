<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue;

interface DictionaryValue {
    public static function fromValue(string $valueString): ?self;
}
