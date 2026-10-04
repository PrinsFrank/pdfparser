<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\Encoding\MacRoman;
use PrinsFrank\PdfParser\Document\Encoding\WinAnsi;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

enum EncodingNameValue: string implements NameValue {
    case IdentityV = 'Identity-V';
    case IdentityH = 'Identity-H';
    case MacRomanEncoding = 'MacRomanEncoding';
    case MacExpertEncoding = 'MacExpertEncoding';
    case WinAnsiEncoding = 'WinAnsiEncoding';

    public function decodeString(string $characterGroup): string {
        return match ($this) {
            self::WinAnsiEncoding => WinAnsi::textToUnicode($characterGroup),
            self::MacRomanEncoding => MacRoman::textToUnicode($characterGroup),
            default => throw new ParseFailureException(sprintf('Unsupported encoding %s', $this->name)),
        };
    }
}
