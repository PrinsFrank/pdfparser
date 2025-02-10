<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Character;

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
    case CHARACTER_CODE = '\ddd';
}
