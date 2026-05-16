<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;

/** @api */
readonly class DictionaryEntry {
    public function __construct(
        public DictionaryKey|ExtendedDictionaryKey  $key,
        public DictionaryValue|Dictionary|NameValue $value,
    ) {}
}
