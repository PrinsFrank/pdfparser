<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Character;

/**
 * @internal
 *
 * @see Pdf 32000-1:2008 7.2.2 (Table 1)
 *
 * The White-space characters separate syntactic constructs such as names and numbers from
 * each other. All white-space characters are equivalent, except in comments, strings, and streams. In all other
 * contexts, PDF treats any sequence of consecutive white-space characters as one character.
 *
 * The CARRIAGE RETURN (0Dh) and LINE FEED (0Ah) characters, also called newline characters, shall be
 * treated as end-of-line (EOL) markers. The combination of a CARRIAGE RETURN followed immediately by a
 * LINE FEED shall be treated as one EOL marker. EOL markers may be treated the same as any other white-
 * space characters. However, sometimes an EOL marker is required or recommended—that is, preceding a
 * token that must appear at the beginning of a line.
 */
enum WhitespaceCharacter: string {
    case NULL = "\000";
    case HORIZONTAL_TAB = "\t";
    case LINE_FEED = "\n";
    case FORM_FEED = "\f";
    case CARRIAGE_RETURN = "\r";
    case SPACE = "\040";
}
