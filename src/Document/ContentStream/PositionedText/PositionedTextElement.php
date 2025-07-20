<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Generic\Character\LiteralStringEscapeCharacter;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class PositionedTextElement {
    public function __construct(
        public readonly string               $rawTextContent,
        public readonly TransformationMatrix $absoluteMatrix,
        public readonly TextState            $textState,
    ) {
    }

    public function getFont(Document $document, Page $page): Font {
        if ($this->textState->fontName === null) {
            throw new ParseFailureException('Unable to locate font for text element');
        }

        return $page->getFontDictionary()?->getObjectForReference($document, $this->textState->fontName, Font::class)
            ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $this->textState->fontName->value));
    }

    /** @throws ParseFailureException */
    public function getText(Document $document, Page $page): string {
        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((\\\\\)|[^)])*\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $this->rawTextContent, $matches, PREG_SET_ORDER)) === false) {
            throw new ParseFailureException(sprintf('Error with regex'));
        } elseif ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $this->rawTextContent));
        }

        $string = '';
        $font = $this->getFont($document, $page);
        foreach ($matches as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $chars = LiteralStringEscapeCharacter::unescapeCharacters(substr($match['chars'], 1, -1));
                if (($encoding = $font->getEncoding()) !== null) {
                    $chars = $encoding->decodeString($chars);
                } elseif (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                    $chars = $toUnicodeCMap->textToUnicode(bin2hex($chars));
                }

                $string .= $chars;
            } elseif (str_starts_with($match['chars'], '<') && str_ends_with($match['chars'], '>')) {
                $chars = substr($match['chars'], 1, -1);
                if (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                    $string .= $toUnicodeCMap->textToUnicode($chars);
                } elseif (($encoding = $font->getEncoding()) !== null) {
                    $string .= $encoding->decodeString(implode('', array_map(fn (string $character) => mb_chr((int) hexdec($character)), str_split($chars, 2))));
                } else {
                    throw new ParseFailureException('Unable to use CMap or decode string to retrieve characters for text object');
                }
            } else {
                throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $match['chars']));
            }

            if (isset($match['offset']) && (float) $match['offset'] < -100) {
                $string .= ' ';
            }
        }

        return $string;
    }

    /** @return list<int> */
    public function getCodePoints(): array {
        $codePoints = [];
        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((\\\\\)|[^)])*\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $this->rawTextContent, $matches, PREG_SET_ORDER)) === false) {
            throw new ParseFailureException(sprintf('Error with regex'));
        } elseif ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $this->rawTextContent));
        }

        foreach ($matches as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $chars = str_replace(['\(', '\)', '\n', '\r'], ['(', ')', "\n", "\r"], substr($match['chars'], 1, -1));
                $chars = preg_replace_callback('/\\\\([0-7]{3})/', fn (array $matches) => mb_chr((int) octdec($matches[1])), $chars)
                    ?? throw new ParseFailureException();
                foreach (mb_str_split($chars) as $char) {
                    $codePoints[] = ord($char);
                }
            } elseif (str_starts_with($match['chars'], '<') && str_ends_with($match['chars'], '>')) {
                foreach (str_split(substr($match['chars'], 1, -1), 4) as $char) {
                    $codePoints[] = is_int($codePoint = hexdec($char)) ? $codePoint : throw new ParseFailureException();
                }
            } else {
                throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $match['chars']));
            }
        }

        return $codePoints;
    }
}
