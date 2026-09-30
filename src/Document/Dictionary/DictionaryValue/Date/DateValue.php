<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date;

use DateTimeImmutable;
use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use ValueError;

/**
 * @api
 *
 * @see 7.9.4
 */
readonly class DateValue implements DictionaryValue {
    public function __construct(
        public ?DateTimeImmutable $value,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '<') && str_ends_with($valueString, '>')) {
            $valueString = substr($valueString, 1, -1);
            if ($valueString === '') {
                return new self(null);
            }

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
                fn(array $matches) => mb_chr((int) octdec($matches[1])),
                substr($valueString, 1, -1),
            ) ?? throw new ParseFailureException();

            if ($valueString === '') {
                return new self(null);
            }
        }

        if (!str_starts_with($valueString, 'D:')) {
            $valueString = mb_convert_encoding($valueString, 'UTF-8', 'UTF-16');
            if ($valueString === false || !str_starts_with($valueString, 'D:')) {
                return null;
            }
        }

        try {
            $valueString = preg_replace('/Z(\d)/', '+$1', $valueString) ?? throw new ValueError();
            $datePart = preg_match('/^D:(\d+)/', $valueString, $matches) === 1 ? $matches[1] : '';
            if (in_array(strlen($datePart), [4, 6, 8, 10, 12, 14], true) === false) { // Only year, optionally month, day, hour, minute and second
                return null;
            }

            $defaults = '0101000000';
            $valueString = 'D:' . $datePart . substr($defaults, strlen($datePart) - 4) . substr($valueString, 2 + strlen($datePart));
            $parsedDate = DateTimeImmutable::createFromFormat(
                preg_match('/^D:\d{14}$/', $valueString) === 1 ? '\D\:YmdHis' : '\D\:YmdHisP',
                str_replace("'", '', $valueString),
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
