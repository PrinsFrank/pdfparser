<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date;

use DateTimeImmutable;
use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
class DateValue implements DictionaryValue {
    public function __construct(
        public readonly ?DateTimeImmutable $value
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (str_starts_with($valueString, '<') && str_ends_with($valueString, '>')) {
            $valueString = substr($valueString, 1, -1);
            $valueString = hex2bin($valueString);
            if ($valueString === false) {
                return null;
            }
        }

        if (str_starts_with($valueString, '(') && str_ends_with($valueString, ')')) {
            $valueString = substr($valueString, 1, -1);
        }

        if (!str_starts_with($valueString, 'D:')) {
            $valueString = mb_convert_encoding($valueString, 'UTF-8', 'UTF-16');
            if (!str_starts_with($valueString, 'D:')) {
                return null;
            }
        }

        $parsedDate = DateTimeImmutable::createFromFormat('\D\:YmdHisP', str_replace("'", '', $valueString));
        if ($parsedDate === false) {
            return null;
        }

        return new self($parsedDate);
    }
}
