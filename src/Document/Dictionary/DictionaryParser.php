<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext\DictionaryParseContext;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext\NestingContext;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\LiteralStringEscapeCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Security\EncryptionContext;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @internal */
class DictionaryParser {
    /**
     * @phpstan-assert int<0, max> $startPos
     * @phpstan-assert int<1, max> $nrOfBytes
     *
     * @throws PdfParserException
     */
    public static function parse(?EncryptionContext $encryptionContext, Stream $stream, int $startPos, int $nrOfBytes): Dictionary {
        $dictionaryArray = [];
        $nestingContext = (new NestingContext())->setContext(DictionaryParseContext::ROOT);
        $arrayNestingLevel = 0;
        $previousChar = $secondToLastChar = $currentContext = $contextBeforeComment = $previousIndexLevelDecrease = $previousIndexLevelIncrease = null;
        foreach ($stream->chars($startPos, $nrOfBytes) as $index => $char) {
            if ($char === DelimiterCharacter::LESS_THAN_SIGN->value
                && $previousChar === DelimiterCharacter::LESS_THAN_SIGN->value
                && $secondToLastChar !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value
                && $currentContext !== DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS
                && $previousIndexLevelIncrease !== $index - 1) {
                if ($currentContext === DictionaryParseContext::KEY) {
                    $nestingContext->removeCharFromKeyBuffer();
                }

                $previousIndexLevelIncrease = $index;
                $nestingContext->setContext(DictionaryParseContext::DICTIONARY)->incrementNesting()->setContext(DictionaryParseContext::DICTIONARY);
                $currentContext = DictionaryParseContext::DICTIONARY;
            } elseif ($char === DelimiterCharacter::LESS_THAN_SIGN->value && $currentContext === DictionaryParseContext::KEY) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
                $currentContext = DictionaryParseContext::VALUE;
            } elseif ($char === DelimiterCharacter::GREATER_THAN_SIGN->value
                && $previousChar === DelimiterCharacter::GREATER_THAN_SIGN->value
                && $secondToLastChar !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value
                && $currentContext !== DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS
                && $previousIndexLevelDecrease !== $index - 1) {
                $nestingContext->removeCharFromValueBuffer();
                self::flush($dictionaryArray, $nestingContext);
                $previousIndexLevelDecrease = $index;
                $nestingContext->decrementNesting()->flush();
                $currentContext = $nestingContext->getContext();
            } elseif ($char === DelimiterCharacter::SOLIDUS->value
                && $previousChar !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value
                && $currentContext !== DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS) {
                if ($currentContext === DictionaryParseContext::DICTIONARY) {
                    $nestingContext->setContext(DictionaryParseContext::KEY);
                    $currentContext = DictionaryParseContext::KEY;
                } elseif ($currentContext === DictionaryParseContext::VALUE) {
                    self::flush($dictionaryArray, $nestingContext);
                    $nestingContext->setContext(DictionaryParseContext::KEY);
                    $currentContext = DictionaryParseContext::KEY;
                } elseif ($currentContext === DictionaryParseContext::KEY || $currentContext === DictionaryParseContext::KEY_VALUE_SEPARATOR) {
                    $nestingContext->setContext(DictionaryParseContext::VALUE);
                    $currentContext = DictionaryParseContext::VALUE;
                }
            } elseif ($char === WhitespaceCharacter::LINE_FEED->value && $currentContext !== DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS) {
                if ($currentContext === DictionaryParseContext::KEY) {
                    $nestingContext->setContext(DictionaryParseContext::KEY_VALUE_SEPARATOR);
                    $currentContext = DictionaryParseContext::KEY_VALUE_SEPARATOR;
                } elseif ($currentContext === DictionaryParseContext::VALUE) {
                    self::flush($dictionaryArray, $nestingContext);
                } elseif ($currentContext === DictionaryParseContext::COMMENT) {
                    $nestingContext->setContext($contextBeforeComment ?? DictionaryParseContext::DICTIONARY);
                    $currentContext = $contextBeforeComment ?? DictionaryParseContext::DICTIONARY;
                    $contextBeforeComment = null;
                }
            } elseif ($char === DelimiterCharacter::PERCENT_SIGN->value && $previousChar !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value && $currentContext !== DictionaryParseContext::VALUE_IN_PARENTHESES) {
                if ($currentContext === DictionaryParseContext::VALUE) {
                    self::flush($dictionaryArray, $nestingContext);
                    $contextBeforeComment = DictionaryParseContext::DICTIONARY;
                } else {
                    $contextBeforeComment = $currentContext;
                }
                $nestingContext->setContext(DictionaryParseContext::COMMENT);
                $currentContext = DictionaryParseContext::COMMENT;
            } elseif (WhitespaceCharacter::tryFrom($char) !== null && $currentContext === DictionaryParseContext::KEY) {
                $nestingContext->setContext(DictionaryParseContext::KEY_VALUE_SEPARATOR);
                $currentContext = DictionaryParseContext::KEY_VALUE_SEPARATOR;
            } elseif ($char === DelimiterCharacter::LEFT_PARENTHESIS->value && ($currentContext === DictionaryParseContext::KEY || $currentContext === DictionaryParseContext::KEY_VALUE_SEPARATOR || $currentContext === DictionaryParseContext::VALUE)) {
                $nestingContext->setContext(DictionaryParseContext::VALUE_IN_PARENTHESES);
                $currentContext = DictionaryParseContext::VALUE_IN_PARENTHESES;
            } elseif ($char === DelimiterCharacter::RIGHT_PARENTHESIS->value && $previousChar !== LiteralStringEscapeCharacter::REVERSE_SOLIDUS->value && $currentContext === DictionaryParseContext::VALUE_IN_PARENTHESES) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
                $currentContext = DictionaryParseContext::VALUE;
            } elseif ($char === DelimiterCharacter::LEFT_SQUARE_BRACKET->value && ($currentContext === DictionaryParseContext::KEY || $currentContext === DictionaryParseContext::KEY_VALUE_SEPARATOR || $currentContext === DictionaryParseContext::VALUE || $currentContext === DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS)) {
                $nestingContext->setContext(DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS);
                $currentContext = DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS;
                $arrayNestingLevel++;
            } elseif ($char === DelimiterCharacter::RIGHT_SQUARE_BRACKET->value && $currentContext === DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS) {
                $arrayNestingLevel--;
                if ($arrayNestingLevel === 0) {
                    $nestingContext->setContext(DictionaryParseContext::VALUE);
                    $currentContext = DictionaryParseContext::VALUE;
                }
            } elseif (trim($char) !== '' && $currentContext === DictionaryParseContext::KEY_VALUE_SEPARATOR) {
                $nestingContext->setContext(DictionaryParseContext::VALUE);
                $currentContext = DictionaryParseContext::VALUE;
            }

            $secondToLastChar = $previousChar;
            $previousChar = $char;
            if ($currentContext === DictionaryParseContext::KEY) {
                $nestingContext->addToKeyBuffer($char);
            } elseif ($currentContext === DictionaryParseContext::VALUE_IN_PARENTHESES
                || $currentContext === DictionaryParseContext::VALUE_IN_SQUARE_BRACKETS
                || $currentContext === DictionaryParseContext::VALUE) {
                $nestingContext->addToValueBuffer($char);
            }
        }

        return DictionaryFactory::fromArray($encryptionContext, $dictionaryArray);
    }

    /** @param array<string, mixed> $dictionaryArray */
    private static function flush(array &$dictionaryArray, NestingContext $nestingContext): void {
        if (($valueBuffer = $nestingContext->getValueBuffer()) === '' || ($keyBuffer = $nestingContext->getKeyBuffer()) === '') {
            return;
        }

        $dictionaryArrayPointer = &$dictionaryArray;
        foreach (($keys = $nestingContext->getKeysFromRoot()) as $index => $key) {
            if ($key === $keyBuffer && $index === array_key_last($keys)) {
                break;
            }

            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            $dictionaryArrayPointer = &$dictionaryArrayPointer[trim($key)];
        }

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $dictionaryArrayPointer[$keyBuffer] = trim($valueBuffer);
        $nestingContext->flush();
    }
}
