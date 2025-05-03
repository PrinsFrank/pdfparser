<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class PositionedTextElement {
    public function __construct(
        public readonly string               $rawTextContent,
        public readonly TransformationMatrix $absoluteMatrix,
        public readonly ?TextState           $textState,
    ) {
    }

    public function getText(?Font $font): string {
        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((\\\\\)|[^)])*\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $this->rawTextContent, $matches, PREG_SET_ORDER)) === false) {
            throw new ParseFailureException(sprintf('Error with regex'));
        } elseif ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $this->rawTextContent));
        }

        $string = '';
        foreach ($matches as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $chars = str_replace(['\(', '\)', '\n', '\r'], ['(', ')', "\n", "\r"], substr($match['chars'], 1, -1));
                $chars = preg_replace_callback('/\\\\([0-7]{3})/', fn (array $matches) => mb_chr((int) octdec($matches[1])), $chars)
                    ?? throw new ParseFailureException();
                if ($font !== null && ($encoding = $font->getEncoding()) !== null) {
                    $chars = $encoding->decodeString($chars);
                }

                $string .= $chars;
            } elseif (str_starts_with($match['chars'], '<') && str_ends_with($match['chars'], '>')) {
                if ($font === null) {
                    throw new ParseFailureException('No font available');
                }

                $string .= $font->toUnicode(substr($match['chars'], 1, -1));
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
                    $codePoints[] = hexdec($char);
                }
            } else {
                throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $match['chars']));
            }
        }

        return $codePoints;
    }
}
