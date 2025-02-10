<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;

/** @api */
class DictionaryEntry {
    public function __construct(
        public readonly DictionaryKey|ExtendedDictionaryKey $key,
        public readonly DictionaryValue|Dictionary|NameValue $value,
    ) {
    }
}
