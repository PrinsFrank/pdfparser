<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text\OperatorString;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextState;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use RuntimeException;

/** @internal */
enum TextStateOperator: string {
    case CHAR_SPACE = 'Tc';
    case WORD_SPACE = 'Tw';
    case SCALE = 'Tz';
    case LEADING = 'TL';
    case FONT_SIZE = 'Tf';
    case RENDER = 'Tr';
    case RISE = 'Ts';

    public function getNewTextState(string $operand, ?TextState $textState): TextState {
        if ($this === self::CHAR_SPACE) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                (float) $operand,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::WORD_SPACE) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace ?? 0,
                (float) $operand,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::SCALE) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                (int) $operand,
                $textState->leading ?? 0,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::LEADING) {
            return new TextState(
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                (float) $operand,
                $textState->render ?? 0,
                $textState->rise ?? 0,
            );
        }

        if ($this === self::FONT_SIZE) {
            if (preg_match('/^\/(?<fontReference>[A-Za-z_0-9\.\-]+)\s+(?<FontSize>[0-9]+(\.[0-9]+)?)$/', $operand, $matches) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid font operand "%s" for Tf operator', substr($operand, 0, 200)));
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
                $textState->fontName,
                $textState->fontSize,
                $textState->charSpace ?? 0,
                $textState->wordSpace ?? 0,
                $textState->scale ?? 100,
                $textState->leading ?? 0,
                (int) $operand,
                $textState->rise ?? 0,
            );
        }

        return new TextState(
            $textState->fontName,
            $textState->fontSize,
            $textState->charSpace ?? 0,
            $textState->wordSpace ?? 0,
            $textState->scale ?? 100,
            $textState->leading ?? 0,
            $textState->render ?? 0,
            (float) $operand,
        );
    }

    /** @deprecated */
    public function getFontReference(string $operand): DictionaryKey|ExtendedDictionaryKey {
        if ($this !== self::FONT_SIZE) {
            throw new InvalidArgumentException('Can only retrieve font for Tf operator');
        }

        if (preg_match('/^\/(?<fontReference>[A-Za-z_0-9\.\-]+)\s+[0-9]+(\.[0-9]+)?$/', $operand, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid font operand "%s" for Tf operator', substr($operand, 0, 200)));
        }

        if (($dictionaryKey = DictionaryKey::tryFrom($matches['fontReference'])) !== null) {
            return $dictionaryKey;
        }

        return new ExtendedDictionaryKey($matches['fontReference']);
    }
}
