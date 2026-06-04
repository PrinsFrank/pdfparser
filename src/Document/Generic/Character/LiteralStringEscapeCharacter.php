<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Character;

use PrinsFrank\PdfParser\Exception\ParseFailureException;

/**
 * @internal
 *
 * @see Pdf 32000-1:2008 7.3.4.2 Table 3
 */
enum LiteralStringEscapeCharacter: string {
    case LINE_FEED = '\n';
    case CARRIAGE_RETURN = '\r';
    case HORIZONTAL_TAB = '\t';
    case BACKSPACE = '\b';
    case FORM_FEED = '\f';
    case LEFT_PARENTHESIS = '\(';
    case RIGHT_PARENTHESIS = '\)';
    case REVERSE_SOLIDUS = '\\';

    public static function unescapeCharacters(string $string): string {
        $string = str_replace("\\\n", '', $string); // Example 2, 7.3.4.2 newlines preceded by reverse solidus should be handled like single lines

        return preg_replace_callback(
            '/\\\\([0-7]{1,3})/',
            static function (array $matches) {
                $decimal = octdec($matches[1]);
                if (!is_int($decimal) || $decimal < 0 || $decimal > 255) {
                    throw new ParseFailureException(sprintf('Invalid octal value "%s"', $matches[1]));
                }

                return mb_chr($decimal);
            },
            str_replace(['\n', '\r', '\t', '\b', '\f', '\(', '\)', '\\'], ["\n", "\r", "\t", "\x08", "\x0C", "(", ")", "\\"], $string),
        ) ?? throw new ParseFailureException();
    }
}
