<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\Operator;

enum TextPositioningOperator: string
{
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'TM';
    case NEXT_LINE = 'T*';
}
