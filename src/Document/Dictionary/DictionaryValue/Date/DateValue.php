<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date;

use DateTimeImmutable;
use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

/** @api */
class DateValue implements DictionaryValue {
    public function __construct(
        public readonly ?DateTimeImmutable $value
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        // FEFF0044003A00320030003200300030003900330030003100350034003300300037005A
        // UTF-16BE encoded string.
        if (preg_match('/<FEFF([A-Z0-9]{68})>/', $valueString, $matches)) {
            $bin = hex2bin($matches[1]);
            if (substr($bin, 0, 2) === "\xFE\xFF") {
                $bin = substr($bin, 2); // remove BOM
            }
            return self::fromValue(mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE'));
        }

        if (str_starts_with($valueString, '(') && str_ends_with($valueString, ')')) {
            $valueString = substr($valueString, 1, -1);
        }

        if (!str_starts_with($valueString, 'D:')) {
            $valueString = mb_convert_encoding($valueString, 'UTF-8', mb_detect_encoding($valueString));
            if ($valueString === false || !str_starts_with($valueString, 'D:')) {
                return null;
            }
        }

        $parsedDate = false;
        if (preg_match("/^D:[0-9]{14}$/", $valueString) === 1) {
            $parsedDate = DateTimeImmutable::createFromFormat('\D\:YmdHis', $valueString);
        }
        if (preg_match("/^D:[0-9]{14}Z$/", $valueString) === 1) {
            $parsedDate = DateTimeImmutable::createFromFormat('\D\:YmdHisT', $valueString);
        }
        if (preg_match("/^D:[0-9]{14}[-+][0-9][0-9]'[0-9][0-9]'$/", $valueString) === 1) {
            $parsedDate = DateTimeImmutable::createFromFormat('\D\:YmdHisP', str_replace("'", '', $valueString));
        }
        if ($parsedDate === false) {
            return null;
        }

        return new self($parsedDate);
    }
}
