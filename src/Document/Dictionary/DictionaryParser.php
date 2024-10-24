<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext\DictionaryParseContext;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext\NestingContext;
use PrinsFrank\PdfParser\Document\Errors\ErrorCollection;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\LiteralStringEscapeCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Generic\Parsing\RollingCharBuffer;
use PrinsFrank\PdfParser\Exception\BufferTooSmallException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/**
 * << start object
 * >> end object
 * [ start array
 * ] end array
 * / start key
 */
class DictionaryParser {
    /**
     * @throws BufferTooSmallException
     * @throws ParseFailureException
     */
    public static function parse(string $content, ErrorCollection $errorCollection): Dictionary {
        $dictionaryArray = [];
        $rollingCharBuffer = new RollingCharBuffer(6);
        $nestingContext = (new NestingContext())->setContext(DictionaryParseContext::ROOT);
        foreach (str_split($content) as $char) {
            $rollingCharBuffer->next()->setCharacter($char);
            if ($rollingCharBuffer->seenBackedEnumValue(Marker::STREAM)) {
                break;
            }

            if ($char === DelimiterCharacter::LESS_THAN_SIGN->value
                && $rollingCharBuffer->getPreviousCharacter() === DelimiterCharacter::LESS_THAN_SIGN->value
                && $rollingCharBuffer->getPreviousCharacter(2) !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value) {
                if ($nestingContext->getContext() === DictionaryParseContext::KEY) {
                    $nestingContext->removeFromKeyBuffer();
                }

                $nestingContext->setContext(DictionaryParseContext::DICTIONARY)->incrementNesting()->setContext(DictionaryParseContext::DICTIONARY);
            } elseif ($char === DelimiterCharacter::GREATER_THAN_SIGN->value
                && $rollingCharBuffer->getPreviousCharacter() === DelimiterCharacter::GREATER_THAN_SIGN->value
                && $rollingCharBuffer->getPreviousCharacter(2) !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value) {
                $nestingContext->removeFromValueBuffer();
                self::flush($dictionaryArray, $nestingContext);
                $nestingContext->decrementNesting()->flush();
            } elseif ($char === DelimiterCharacter::SOLIDUS->value && $rollingCharBuffer->getPreviousCharacter() !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value) {
                if ($nestingContext->getContext() === DictionaryParseContext::DICTIONARY) {
                    $nestingContext->setContext(DictionaryParseContext::KEY);
                } elseif ($nestingContext->getContext() === DictionaryParseContext::VALUE) {
                    self::flush($dictionaryArray, $nestingContext);
                    $nestingContext->setContext(DictionaryParseContext::KEY);
                } elseif ($nestingContext->getContext() === DictionaryParseContext::KEY || $nestingContext->getContext() === DictionaryParseContext::KEY_VALUE_SEPARATOR) {
                    $nestingContext->setContext(DictionaryParseContext::VALUE);
                }
            } elseif ($char === LiteralStringEscapeCharacter::LINE_FEED->value) {
                if ($nestingContext->getContext() === DictionaryParseContext::KEY) {
                    $nestingContext->setContext(DictionaryParseContext::VALUE);
                } elseif ($nestingContext->getContext() === DictionaryParseContext::VALUE) {
                    self::flush($dictionaryArray, $nestingContext);
                } elseif ($nestingContext->getContext() === DictionaryParseContext::COMMENT) {
                    $nestingContext->setContext(DictionaryParseContext::DICTIONARY);
                }
            } elseif ($char === WhitespaceCharacter::SPACE->value && $nestingContext->getContext() === DictionaryParseContext::KEY) {
                $nestingContext->setContext(DictionaryParseContext::KEY_VALUE_SEPARATOR);
            } elseif ($char === DelimiterCharacter::LEFT_PARENTHESIS->value
                       && (in_array($nestingContext->getContext(), [DictionaryParseContext::KEY, DictionaryParseContext::KEY_VALUE_SEPARATOR, DictionaryParseContext::VALUE], true))) {
                $nestingContext->setContext(DictionaryParseContext::VALUE_IN_PARENTHESES);
            } elseif ($char === DelimiterCharacter::RIGHT_PARENTHESIS->value && $nestingContext->getContext() === DictionaryParseContext::VALUE_IN_PARENTHESES) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
            } elseif ($char === DelimiterCharacter::LEFT_SQUARE_BRACKET->value
                       && (in_array($nestingContext->getContext(), [DictionaryParseContext::KEY, DictionaryParseContext::KEY_VALUE_SEPARATOR, DictionaryParseContext::VALUE], true))) {
                $nestingContext->setContext(DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS);
            } elseif ($char === DelimiterCharacter::RIGHT_SQUARE_BRACKET->value && $nestingContext->getContext() === DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
            } elseif (trim($char) !== '' && $nestingContext->getContext() === DictionaryParseContext::KEY_VALUE_SEPARATOR) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
            } elseif ($char === DelimiterCharacter::PERCENT_SIGN->value && $rollingCharBuffer->getPreviousCharacter() !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value) {
                $nestingContext->setContext(DictionaryParseContext::COMMENT);
            }

            match ($nestingContext->getContext()) {
                DictionaryParseContext::KEY => $nestingContext->addToKeyBuffer($char),
                DictionaryParseContext::VALUE_IN_PARENTHESES,
                DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS,
                DictionaryParseContext::VALUE => $nestingContext->addToValueBuffer($char),
                default => null,
            };
        }

        return DictionaryFactory::fromArray($dictionaryArray, $errorCollection);
    }

    private static function flush(array &$dictionaryArray, NestingContext $nestingContext): void {
        if ($nestingContext->getValueBuffer()->isEmpty() || $nestingContext->getKeyBuffer()->isEmpty()) {
            return;
        }

        $dictionaryArrayPointer = &$dictionaryArray;
        foreach ($nestingContext->getKeysFromRoot() as $key) {
            if ($key === (string) $nestingContext->getKeyBuffer()) {
                break;
            }

            $dictionaryArrayPointer = &$dictionaryArrayPointer[trim($key)];
        }

        $dictionaryArrayPointer[(string) $nestingContext->getKeyBuffer()] = trim((string) $nestingContext->getValueBuffer());
        $nestingContext->flush()->setContext(DictionaryParseContext::ROOT);
    }
}
