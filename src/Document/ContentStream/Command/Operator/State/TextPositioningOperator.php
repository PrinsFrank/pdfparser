<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
enum TextPositioningOperator: string implements InteractsWithTransformationMatrix, InteractsWithTextState {
    case MOVE_OFFSET = 'Td';
    case MOVE_OFFSET_LEADING = 'TD';
    case SET_MATRIX = 'Tm';
    case NEXT_LINE = 'T*';

    /** @throws ParseFailureException */
    #[Override]
    public function applyToTransformationMatrix(string $operands, TransformationMatrix $transformationMatrix, ?TextState $textState): TransformationMatrix {
        $operands = preg_replace('/\s+/', ' ', $operands) ?? throw new RuntimeException();
        if ($this === self::MOVE_OFFSET || $this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new ParseFailureException();
            }

            $tx = (float) $offsets[0];
            $ty = (float) $offsets[1];

            return (new TransformationMatrix(1, 0, 0, 1, $tx, $ty))
                ->multiplyWith($transformationMatrix);
        }

        if ($this === self::SET_MATRIX) {
            $matrix = explode(' ', trim($operands));
            if (count($matrix) !== 6) {
                throw new ParseFailureException();
            }

            return new TransformationMatrix((float) $matrix[0], (float) $matrix[1], (float) $matrix[2], (float) $matrix[3], (float) $matrix[4], (float) $matrix[5]);
        }

        $tx = 0.0;
        $ty = -($textState->leading ?? 0.0);

        return (new TransformationMatrix(1, 0, 0, 1, $tx, $ty))
            ->multiplyWith($transformationMatrix);
    }

    /** @throws ParseFailureException */
    #[Override]
    public function applyToTextState(string $operands, TextState $textState): TextState {
        if ($this === self::MOVE_OFFSET_LEADING) {
            $offsets = explode(' ', trim($operands));
            if (count($offsets) !== 2) {
                throw new ParseFailureException();
            }

            return $textState->withLeading(-1 * (float) $offsets[1]);
        }

        return $textState;
    }
}
