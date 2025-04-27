<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;

/** @internal */
enum TextPositioningOperator: string implements InteractsWithTextMatrix, InteractsWithTextState {
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'Tm';
    case NEXT_LINE = 'T*';

    public function applyToTextMatrix(string $operands, TextMatrix $currentTextMatrix): TextMatrix {
        if ($this === self::MOVE_OFFSET || $this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new \RuntimeException();
            }

            return new TextMatrix(
                $currentTextMatrix->scaleX,
                $currentTextMatrix->shearX,
                $currentTextMatrix->shearY,
                $currentTextMatrix->scaleY,
                $currentTextMatrix->offsetX + (float) $offsets[0],
                $currentTextMatrix->offsetY + (float) $offsets[1],
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
            $currentTextMatrix->scaleX,
            $currentTextMatrix->shearX,
            $currentTextMatrix->shearY,
            $currentTextMatrix->scaleY,
            0,
            $currentTextMatrix->offsetY,
        );
    }

    public function applyToTextState(string $operands, ?TextState $textState): ?TextState {
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
