<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\Registry;

use PrinsFrank\PdfParser\Document\CMap\Registry\Adobe\Identity0;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

/** @internal */
class RegistryOrchestrator {
    public static function getForRegistryOrderingSupplement(
        LiteralStringValue|HexadecimalStringValue|NameObjectStringValue $registry,
        LiteralStringValue|HexadecimalStringValue|NameObjectStringValue $ordering,
        IntegerValue $supplement,
    ): ?CMapResource {
        return match ([$registry->getText(), $ordering->getText(), $supplement->value]) {
            ['Adobe', 'Identity', 0] => new Identity0(),
            default => null,
        };
    }
}
