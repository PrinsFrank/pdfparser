<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\OperatorString;

use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
enum TextPositioningOperator: string {
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'Tm';
    case NEXT_LINE = 'T*';

    public function display(string $operands): string {
        if ($this === self::NEXT_LINE) {
            return "\n";
        }

        if ($this === self::MOVE_OFFSET) {
            $offsets = explode(' ', $operands, 2);
            if (count($offsets) !== 2) {
                throw new RuntimeException();
            }

            [$horizontalOffset, $verticalOffset] = $offsets;
            if ($verticalOffset < -20) {
                return "\n";
            }

            if ($horizontalOffset < -20) {
                return ' ';
            }
        }

        return '';
    }
}
