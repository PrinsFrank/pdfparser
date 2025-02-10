<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext;

/** @internal */
enum DictionaryParseContext {
    case ROOT;
    case DICTIONARY;
    case KEY;
    case KEY_VALUE_SEPARATOR;
    case VALUE;
    case VALUE_IN_PARENTHESES;
    case VALUE_IN_SQUARE_BRACKETS;
    case VALUE_IN_ANGLE_BRACKETS;
    case COMMENT;
}
