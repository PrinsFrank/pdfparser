<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
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
        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((\\\\\)|[^)])*\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $operands, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) === false) {
            throw new ParseFailureException(sprintf('Error with regex'));
        } elseif ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $operands));
        }

        return new PositionedTextElement(
            array_map(
                fn(array $match) => new TextSegment(new TextStringValue($match['chars']), $match['offset'] !== null ? (float) $match['offset'] : null),
                $matches,
            ),
            $textMatrix->multiplyWith($globalTransformationMatrix),  // 9.4.4, Note 2 on Trm calculation
            $textState,
        );
    }
}
