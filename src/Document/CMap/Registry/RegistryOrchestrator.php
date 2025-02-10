<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\Registry;

use PrinsFrank\PdfParser\Document\CMap\Registry\Adobe\Identity0;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

/** @internal */
class RegistryOrchestrator {
    public static function getForRegistryOrderingSupplement(TextStringValue $registry, TextStringValue $ordering, IntegerValue $supplement): ?CMapResource {
        return match ([$registry->getText(), $ordering->getText(), $supplement->value]) {
            ['Adobe', 'Identity', 0] => new Identity0(),
            default => null,
        };
    }
}
