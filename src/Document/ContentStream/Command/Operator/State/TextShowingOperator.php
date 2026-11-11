<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
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
    public function getPositionedTextElement(
        string $operands,
        TransformationMatrix $textMatrix,
        TransformationMatrix $globalTransformationMatrix,
        TextState $textState,
        ColorState $colorState,
    ): PositionedTextElement {
        if ($this === self::SHOW_ARRAY) {
            return new PositionedTextElement($this->parseArray($operands), $textMatrix->multiplyWith($globalTransformationMatrix), $textState);
        }

        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((?:\\\\.|[^\\\\)])*\)))\s*(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $operands, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) === false) {
            throw new ParseFailureException(sprintf('Error with regex'));
        } elseif ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $operands));
        }

        return new PositionedTextElement(
            array_map(
                fn(array $match) => new TextSegment(
                    str_starts_with($match['chars'], '(')
                        ? LiteralStringValue::fromValue($match['chars']) ?? throw new ParseFailureException()
                        : HexadecimalStringValue::fromValue($match['chars']) ?? throw new ParseFailureException(),
                    $match['offset'] !== null ? (float) $match['offset'] : null,
                ),
                $matches,
            ),
            $textMatrix->multiplyWith($globalTransformationMatrix),  // 9.4.4, Note 2 on Trm calculation
            $textState,
        );
    }

    /** @return list<TextSegment> */
    private function parseArray(string $operands): array {
        $operands = trim($operands, "\x00\x09\x0A\x0C\x0D\x20");
        if (!str_starts_with($operands, '[') || !str_ends_with($operands, ']')) {
            throw new ParseFailureException(sprintf('Invalid TJ array "%s"', $operands));
        }

        $contents = trim(substr($operands, 1, -1), "\x00\x09\x0A\x0C\x0D\x20");
        $segments = [];
        $offset = 0;
        while ($offset < strlen($contents)) {
            if (preg_match('/\G[\x00\x09\x0A\x0C\x0D\x20]*(?:(?<chars><[^>]*>|(?<literal>\((?:\\\\[\s\S]|[^\\\\()]|(?&literal))*\)))|(?<number>[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+))(?=[\x00\x09\x0A\x0C\x0D\x20(<]|$))[\x00\x09\x0A\x0C\x0D\x20]*/', $contents, $match, PREG_UNMATCHED_AS_NULL, $offset) !== 1) {
                throw new ParseFailureException(sprintf('Invalid TJ array at byte %d', $offset));
            }

            $offset += strlen($match[0] ?? throw new ParseFailureException());
            if ($match['chars'] !== null) {
                $segments[] = new TextSegment(
                    str_starts_with($match['chars'], '(')
                        ? LiteralStringValue::fromValue($match['chars']) ?? throw new ParseFailureException()
                        : HexadecimalStringValue::fromValue($match['chars']) ?? throw new ParseFailureException(),
                    null,
                );
            } else {
                $last = array_pop($segments);
                $advance = ($last->offset ?? 0) + (float) $match['number'];
                if (!is_finite($advance)) {
                    throw new ParseFailureException('TJ adjustment exceeds the supported numeric range');
                }
                $segments[] = new TextSegment($last->textString ?? new LiteralStringValue(''), $advance);
            }
        }

        return $segments;
    }
}
