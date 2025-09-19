<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
enum TextPositioningOperator: string implements InteractsWithTransformationMatrix, InteractsWithTextState {
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'Tm';
    case NEXT_LINE = 'T*';

    /** @throws ParseFailureException */
    #[Override]
    public function applyToTransformationMatrix(string $operands, TransformationMatrix $transformationMatrix): TransformationMatrix {
        $operands = preg_replace('/\s+/', ' ', $operands);
        if ($this === self::MOVE_OFFSET || $this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new ParseFailureException();
            }

            return new TransformationMatrix(
                $transformationMatrix->scaleX,
                $transformationMatrix->shearX,
                $transformationMatrix->shearY,
                $transformationMatrix->scaleY,
                $transformationMatrix->offsetX + (float) $offsets[0],
                $transformationMatrix->offsetY + (float) $offsets[1]
            );
        }

        if ($this === self::SET_MATRIX) {
            $matrix = explode(' ', trim($operands));
            if (count($matrix) !== 6) {
                throw new ParseFailureException();
            }

            return new TransformationMatrix((float) $matrix[0], (float) $matrix[1], (float) $matrix[2], (float) $matrix[3], (float) $matrix[4], (float) $matrix[5]);
        }

        return $transformationMatrix;
    }

    /** @throws ParseFailureException */
    #[Override]
    public function applyToTextState(string $operands, ?TextState $textState): ?TextState {
        if ($this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new ParseFailureException();
            }

            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
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
