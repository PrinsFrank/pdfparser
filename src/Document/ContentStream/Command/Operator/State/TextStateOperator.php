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
    public function applyToTextState(string $operands, ?TextState $textState): TextState {
        if ($this === self::CHAR_SPACE) {
            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
                (float) $operands,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::WORD_SPACE) {
            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
                $textState->charSpace ?? 0,
                (float) $operands,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::SCALE) {
            $scale = (int) $operands;
            if ($scale < 0 || $scale > 100) {
                throw new ParseFailureException();
            }

            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $scale,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::LEADING) {
            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                (float) $operands,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::FONT_SIZE) {
            if (preg_match('/^\/(?<fontReference>[A-Za-z_0-9\.\-]+)\s+(?<FontSize>[0-9]+(\.[0-9]+)?)$/', $operands, $matches) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid font operand "%s" for Tf operator', substr($operands, 0, 200)));
            }

            return new TextState(
                DictionaryKey::tryFrom($matches['fontReference']) ?? new ExtendedDictionaryKey($matches['fontReference']),
                (float) $matches['FontSize'],
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::RENDER) {
            return new TextState(
                $textState->fontName ?? null,
                $textState->fontSize ?? null,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                (int) $operands,
                $textState->rise ?? 0,
            );
        }

        return new TextState(
            $textState->fontName ?? null,
            $textState->fontSize ?? null,
            $textState->charSpace ?? 0,
            $textState->wordSpace ?? 0,
            $textState->scale ?? 100,
            $textState->leading ?? 0,
            $textState->render ?? 0,
            (float) $operands,
        );
    }
}
