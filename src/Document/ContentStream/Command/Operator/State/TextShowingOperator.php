<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
enum TextShowingOperator: string implements InteractsWithTextState, ProducesPositionedTextElements, InteractsWithTransformationMatrix {
    case SHOW = 'Tj';
    case MOVE_SHOW = '\'';
    case MOVE_SHOW_SPACING = '"';
    case SHOW_ARRAY = 'TJ';

    #[Override]
    public function applyToTransformationMatrix(string $operands, TransformationMatrix $transformationMatrix, TextState $textState): TransformationMatrix {
        if ($this === self::MOVE_SHOW || $this === self::MOVE_SHOW_SPACING) {
            return (new TransformationMatrix(1, 0, 0, 1, 0.0, -$textState->leading))
                ->multiplyWith($transformationMatrix);
        }

        return $transformationMatrix;
    }

    /** @throws ParseFailureException */
    #[Override]
    public function applyToTextState(string $operands, TextState $textState): TextState {
        if ($this === self::MOVE_SHOW_SPACING) {
            $spacing = explode(' ', trim($operands));
            if (count($spacing) !== 2) {
                throw new ParseFailureException();
            }

            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                (float) $spacing[1],
                (float) $spacing[0],
                $textState->scale,
                $textState->leading,
                $textState->render,
                $textState->rise,
            );
        }

        return $textState;
    }

    #[Override]
    public function getPositionedTextElement(string $operands, TransformationMatrix $textMatrix, TransformationMatrix $globalTransformationMatrix, TextState $textState): PositionedTextElement {
        return new PositionedTextElement(
            $operands,
            $globalTransformationMatrix->multiplyWith($textMatrix),
            $textState,
        );
    }
}
