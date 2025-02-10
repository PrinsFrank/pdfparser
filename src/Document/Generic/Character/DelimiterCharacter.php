<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Character;

/**
 * @internal
 *
 * @see Pdf 32000-1:2008 7.2.2 (Table 2)
 *
 * The delimiter characters are special They delimit syntactic entities such as arrays,
 * names, and comments. Any of these characters terminates the entity preceding it and is not included in the
 * entity. Delimiter characters are allowed within the scope of a string when following the rules for composing
 * strings; see 7.3.4.2, “Literal Strings”. The leading ( of a string does delimit a preceding entity and the closing ) of
 * a string delimits the string’s end.
 */
enum DelimiterCharacter: string {
    case LEFT_PARENTHESIS = '(';
    case RIGHT_PARENTHESIS = ')';
    case LESS_THAN_SIGN = '<';
    case GREATER_THAN_SIGN = '>';
    case LEFT_SQUARE_BRACKET = '[';
    case RIGHT_SQUARE_BRACKET = ']';
    case LEFT_CURLY_BRACKET = '{';
    case RIGHT_CURLY_BRACKET = '}';
    case SOLIDUS = '/';

    /**
     * Any occurrence of the PERCENT SIGN outside a string or stream introduces a comment. The comment
     * consists of all characters after the PERCENT SIGN and up to but not including the end of the line, including
     * regular, delimiter, SPACE (20h), and HORIZONTAL TAB characters (09h). A conforming reader shall ignore
     * comments, and treat them as single white-space characters. That is, a comment separates the token preceding
     * it from the one following it.
     *
     * Comments (other than the %PDF–n.m and %%EOF comments described in 7.5, "File Structure") have no
     * semantics. They are not necessarily preserved by applications that edit PDF files
     */
    case PERCENT_SIGN = '%';
}
