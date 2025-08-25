<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @see 7.6.2, table 20, Key V */
enum SecurityAlgorithm: string implements DictionaryValue {
    case UnDocumented = '0';
    case RC4_Or_AES_Key_length_40 = '1';
    case RC4_Or_AES_Key_length_Over_40 = '2';
    case Unpublished_Key_length_Between_40_And_128 = '3';
    case RC4_Or_AES_Key_length_128 = '4';
    case AES_Key_length_256 = '5';

    #[Override]
    public static function fromValue(string $valueString): ?self {
        return self::tryFrom($valueString);
    }
}
