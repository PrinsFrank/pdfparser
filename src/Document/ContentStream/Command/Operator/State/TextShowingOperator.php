<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;

/** @internal */
enum TextShowingOperator: string implements InteractsWithTextState, ProducesPositionedTextElements {
    case SHOW = 'Tj';
    case MOVE_SHOW = '\'';
    case MOVE_SHOW_SPACING = '"';
    case SHOW_ARRAY = 'TJ';

    public function applyToTextState(string $operands, ?TextState $textState): ?TextState {
        if ($this === self::MOVE_SHOW_SPACING) {
            $spacing = explode(' ', trim($operands));
            if (count($spacing) !== 2) {
                throw new \RuntimeException();
            }

            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                (float) $spacing[1],
                (float) $spacing[0],
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        return $textState;
    }

    public function getPositionedTextElement(string $operands, TextMatrix $textMatrix, TextState $textState): ?PositionedTextElement {
        return new PositionedTextElement($operands, $textMatrix, $textState);
    }
}
