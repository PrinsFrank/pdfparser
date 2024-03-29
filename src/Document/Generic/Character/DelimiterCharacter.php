<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Character;

/**
 * @see PDF 32000-1:2008 7.2.2 Table 2
 */
enum DelimiterCharacter: string
{
    case LEFT_PARENTHESIS     = '(';
    case RIGHT_PARENTHESIS    = ')';
    case LESS_THAN_SIGN       = '<';
    case GREATER_THAN_SIGN    = '>';
    case LEFT_SQUARE_BRACKET  = '[';
    case RIGHT_SQUARE_BRACKET = ']';
    case LEFT_CURLY_BRACKET   = '{';
    case RIGHT_CURLY_BRACKET  = '}';
    case SOLIDUS              = '/';
    case PERCENT_SIGN         = '%';
}
