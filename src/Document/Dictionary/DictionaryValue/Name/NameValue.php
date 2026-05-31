<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

/** @api */
interface NameValue {
    public static function tryFrom(string $value): ?static;
}
