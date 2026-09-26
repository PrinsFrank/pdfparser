<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Encoding\PDFDocEncoding;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class HexadecimalStringValue implements DictionaryValue {
    public function __construct(
        public string $value,
    ) {}

    /** @throws ParseFailureException */
    public function getText(): string {
        $binaryValue = $this->getBinaryString();
        if (str_starts_with($binaryValue, "\xFE\xFF")) {
            return mb_convert_encoding(substr($binaryValue, 2), 'UTF-8', 'UTF-16BE');
        }

        if (str_starts_with($binaryValue, "\xFF\xFE")) {
            return mb_convert_encoding(substr($binaryValue, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($binaryValue, "\xEF\xBB\xBF")) {
            return substr($binaryValue, 3);
        }

        return PDFDocEncoding::textToUnicode($binaryValue);
    }

    /** @throws ParseFailureException */
    public function getBinaryString(): string {
        $string = preg_replace('/[\x00\x09\x0A\x0C\x0D\x20]+/', '', $this->value)
            ?? throw new ParseFailureException();
        if (preg_match('/^[0-9A-Fa-f]*$/', $string) !== 1) {
            throw new ParseFailureException(sprintf('Invalid hex string %s', $this->value));
        }

        if (strlen($string) % 2 !== 0) {
            $string .= '0';
        }

        $binaryValue = hex2bin($string);
        if ($binaryValue === false) {
            throw new ParseFailureException('Invalid hex string');
        }

        return $binaryValue;
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '<') === false
            || str_ends_with($valueString, '>') === false) {
            return null;
        }

        return new self(substr($valueString, 1, -1));
    }
}
