<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date;

use DateTimeImmutable;
use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use ValueError;

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
            if (!ctype_xdigit($valueString) || strlen($valueString) % 2 !== 0) {
                throw new InvalidArgumentException(sprintf('String "%s" is not hexadecimal', substr($valueString, 0, 10)));
            }

            $valueString = hex2bin($valueString);
            if ($valueString === false) {
                return null;
            }
        }

        if (str_starts_with($valueString, '(') && str_ends_with($valueString, ')')) {
            $valueString = preg_replace_callback(
                '/\\\\([0-7]{3})/',
                fn (array $matches) => mb_chr((int) octdec($matches[1])),
                substr($valueString, 1, -1)
            ) ?? throw new ParseFailureException();
        }

        if (!str_starts_with($valueString, 'D:')) {
            $valueString = mb_convert_encoding($valueString, 'UTF-8', 'UTF-16');
            if ($valueString === false || !str_starts_with($valueString, 'D:')) {
                return null;
            }
        }

        try {
            $parsedDate = DateTimeImmutable::createFromFormat(
                preg_match('/^D:\d{14}$/', $valueString) === 1 ? '\D\:YmdHis' : '\D\:YmdHisP',
                str_replace("'", '', $valueString)
            );
        } catch (ValueError) {
            return null;
        }

        if ($parsedDate === false) {
            return null;
        }

        return new self($parsedDate);
    }
}
