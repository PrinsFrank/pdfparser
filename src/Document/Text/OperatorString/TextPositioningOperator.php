<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\OperatorString;

use PrinsFrank\PdfParser\Document\Text\Positioning\TextState;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextMatrix;

/** @internal */
enum TextPositioningOperator: string {
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'Tm';
    case NEXT_LINE = 'T*';

    public function getNewTextMatrix(string $operands, TextMatrix $textMatrix, ?TextState $textState): TextMatrix {
        if ($this === self::MOVE_OFFSET || $this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new \RuntimeException();
            }

            return new TextMatrix(
                $textMatrix->scaleX,
                $textMatrix->shearX,
                $textMatrix->shearY,
                $textMatrix->scaleY,
                $textMatrix->offsetX + (float) $offsets[0],
                $textMatrix->offsetY + (float) $offsets[1],
            );
        }

        if ($this === self::SET_MATRIX) {
            $matrix = explode(' ', trim($operands));
            if (count($matrix) !== 6) {
                throw new \RuntimeException();
            }

            return new TextMatrix((float) $matrix[0], (float) $matrix[1], (float) $matrix[2], (float) $matrix[3], (float) $matrix[4], (float) $matrix[5]);
        }

        return new TextMatrix(
            $textMatrix->scaleX,
            $textMatrix->shearX,
            $textMatrix->shearY,
            $textMatrix->scaleY,
            0,
            -1 * ($textState->leading ?? 0),
        );
    }

    public function getNewTextState(string $operands, ?TextState $textState): ?TextState {
        if ($this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new \RuntimeException();
            }

            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                -1 * (float) $offsets[1],
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        return $textState;
    }
}
