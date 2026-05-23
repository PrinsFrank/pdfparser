<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
enum TextStateOperator: string implements InteractsWithTextState {
    case CHAR_SPACE = 'Tc';
    case WORD_SPACE = 'Tw';
    case SCALE = 'Tz';
    case LEADING = 'TL';
    case FONT_SIZE = 'Tf';
    case RENDER = 'Tr';
    case RISE = 'Ts';

    /** @throws ParseFailureException|InvalidArgumentException */
    #[Override]
    public function applyToTextState(string $operands, TextState $textState): TextState {
        if ($this === self::CHAR_SPACE) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                (float) $operands,
                $textState->wordSpace,
                $textState->scale,
                $textState->leading,
                $textState->render,
                $textState->rise,
            );
        }

        if ($this === self::WORD_SPACE) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace,
                (float) $operands,
                $textState->scale,
                $textState->leading,
                $textState->render,
                $textState->rise,
            );
        }

        if ($this === self::SCALE) {
            $trimmedOperands = trim($operands);
            if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/', $trimmedOperands) !== 1) {
                throw new ParseFailureException(sprintf('Invalid scale operand "%s" for scale operator', $operands));
            }

            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace,
                $textState->wordSpace,
                (float) $trimmedOperands,
                $textState->leading,
                $textState->render,
                $textState->rise,
            );
        }

        if ($this === self::LEADING) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace,
                $textState->wordSpace,
                $textState->scale,
                (float) $operands,
                $textState->render,
                $textState->rise,
            );
        }

        if ($this === self::FONT_SIZE) {
            if (preg_match('/^\/(?<fontReference>[A-Za-z_0-9\.\-\+]+)\s+(?<FontSize>-?[0-9]+(\.[0-9]+)?)$/', $operands, $matches) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid font operand "%s" for Tf operator', substr($operands, 0, 200)));
            }

            return new TextState(
                DictionaryKey::tryFrom($matches['fontReference']) ?? new ExtendedDictionaryKey($matches['fontReference']),
                (float) $matches['FontSize'],
                $textState->charSpace,
                $textState->wordSpace,
                $textState->scale,
                $textState->leading,
                $textState->render,
                $textState->rise,
            );
        }

        if ($this === self::RENDER) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace,
                $textState->wordSpace,
                $textState->scale,
                $textState->leading,
                (int) $operands,
                $textState->rise,
            );
        }

        return new TextState(
            $textState->fontName,
            $textState->fontSize,
            $textState->charSpace,
            $textState->wordSpace,
            $textState->scale,
            $textState->leading,
            $textState->render,
            (float) $operands,
        );
    }
}
