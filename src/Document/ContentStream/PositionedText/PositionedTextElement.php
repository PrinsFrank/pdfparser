<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Generic\Character\LiteralStringEscapeCharacter;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class PositionedTextElement {
    public function __construct(
        public string $rawTextContent,
        public TransformationMatrix $absoluteMatrix,
        public TextState $textState,
    ) {}

    public function getFont(Document $document, Page $page): Font {
        if ($this->textState->fontName === null) {
            throw new ParseFailureException('Unable to locate font for text element');
        }

        return $page->getFontDictionary()?->getObjectForReference($document, $this->textState->fontName, Font::class)
            ?? throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $this->textState->fontName->value));
    }

    /** @throws ParseFailureException */
    public function getText(Document $document, Page $page): string {
        $string = '';
        $font = $this->getFont($document, $page);
        foreach ($this->parseOperand() as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $unescapedChars = LiteralStringEscapeCharacter::unescapeCharacters(substr($match['chars'], 1, -1));
                if (preg_match('/^\\\\\d{3}$/', substr($match['chars'], 1, -1)) === 1 && ($glyph = $font->getDifferences()?->getGlyph((int) octdec(substr($match['chars'], 2, -1)))) !== null) {
                    $chars = $glyph->getChar();
                } elseif (strlen($unescapedChars) === 1 && ($glyph = $font->getDifferences()?->getGlyph(ord($unescapedChars))) !== null) {
                    $chars = $glyph->getChar();
                } elseif (in_array($encoding = $font->getEncoding(), [EncodingNameValue::MacExpertEncoding, EncodingNameValue::WinAnsiEncoding], true) && $font->getDifferences() === null) {
                    $chars = $encoding->decodeString($unescapedChars);
                } elseif (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                    $chars = $toUnicodeCMap->textToUnicode(bin2hex($unescapedChars));
                } elseif ($encoding !== null) {
                    $chars = $encoding->decodeString($unescapedChars);
                } else {
                    $chars = $unescapedChars;
                }

                $string .= $chars;
            } elseif (str_starts_with($match['chars'], '<') && str_ends_with($match['chars'], '>')) {
                $chars = substr($match['chars'], 1, -1);
                if (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                    $string .= $toUnicodeCMap->textToUnicode($chars);
                } elseif (($encoding = $font->getEncoding()) !== null) {
                    $string .= $encoding->decodeString(implode('', array_map(fn(string $character) => mb_chr((int) hexdec($character)), str_split($chars, 2))));
                } else {
                    $string .= EncodingNameValue::IdentityH->decodeString($chars);
                }
            } else {
                throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $match['chars']));
            }

            // A large negative TJ adjustment inside a single run is the same word break as a between-element gap,
            // measured directly: −offset/1000 is the gap in em, so it is a word break above the same threshold.
            if ($match['offset'] !== null && $match['offset'] / 1000 <= -ContentStream::WORD_BREAK_THRESHOLD) {
                $string .= ' ';
            }
        }

        return $string;
    }

    /** @return list<int> */
    public function getCodePoints(): array {
        $codePoints = [];
        foreach ($this->parseOperand() as $match) {
            if (str_starts_with($match['chars'], '(') && str_ends_with($match['chars'], ')')) {
                $chars = str_replace(['\(', '\)', '\n', '\r'], ['(', ')', "\n", "\r"], substr($match['chars'], 1, -1));
                $chars = preg_replace_callback('/\\\\([0-7]{3})/', fn(array $matches) => mb_chr((int) octdec($matches[1])), $chars)
                    ?? throw new ParseFailureException();
                foreach (str_split($chars) as $char) {
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

    public function getHeight(): float {
        return ($this->textState->fontSize ?? 12)
            * abs($this->absoluteMatrix->scaleY)
            * ($this->textState->scale / 100);
    }

    /**
     * The distance, in device space, that showing this element advances the text cursor, per the displacement
     * formula in the PDF spec §9.4.4:
     *
     *   ((w0 − Tj/1000)·Tfs + Tc + Tw·[single-byte code 32]) · Th , transformed by the text rendering matrix.
     *
     * Reconstructed here because Tj/TJ do not advance the text matrix in this parser. The text-space advance is
     * horizontal; transformed by the matrix it becomes a page-space vector along the baseline. This returns that
     * vector's signed projection onto the unit vector $direction -- a dot product, so it keeps its sign. The
     * default direction is page X (Vector(1, 0)), giving the advance · scaleX exactly as before; pass a baseline
     * unit vector to measure the advance along a rotated baseline.
     */
    public function getAdvanceWidth(Document $document, Page $page, Vector $direction = new Vector(1.0, 0.0)): float {
        $font = $this->getFont($document, $page);
        $fontSize = $this->textState->fontSize ?? 10;

        $glyphAdvance = $font->getWidthForChars($this->getCodePoints(), $this->textState);            // Σ (w0·Tfs + Tc)
        $wordSpaceAdvance = $this->textState->wordSpace * $this->getWordSpaceableCharacterCount($font); // Tw · #(code 32)
        $kerningAdvance = -($this->getTotalOffset() / 1000) * $fontSize;                                // − Σ(Tj)/1000 · Tfs

        $textSpaceAdvance = ($glyphAdvance + $wordSpaceAdvance + $kerningAdvance) * ($this->textState->scale / 100); // · Th

        // Project the page-space advance vector (the text-space advance along the baseline) onto $direction.
        return $textSpaceAdvance * $this->absoluteMatrix->baselineVector()->dot($direction);
    }

    /** The sum of the TJ adjustment numbers in this element's operand, in thousandths of an em. */
    public function getTotalOffset(): float {
        $totalOffset = 0.0;
        foreach ($this->parseOperand() as $match) {
            if ($match['offset'] !== null) {
                $totalOffset += $match['offset'];
            }
        }

        return $totalOffset;
    }

    /** @return list<array{chars: string, offset: float|null}> */
    private function parseOperand(): array {
        if (($result = preg_match_all('/(?<chars>(<(\\\\>|[^>])*>)|(\((\\\\\)|[^)])*\)))(?<offset>-?[0-9]+(\.[0-9]+)?)?/', $this->rawTextContent, $matches, PREG_SET_ORDER)) === false) {
            throw new ParseFailureException('Error with regex');
        }
        if ($result === 0) {
            throw new ParseFailureException(sprintf('Operands "%s" is not in a recognized format', $this->rawTextContent));
        }

        $operands = [];
        foreach ($matches as $match) {
            $operands[] = [
                'chars' => $match['chars'],
                'offset' => isset($match['offset']) ? (float) $match['offset'] : null,
            ];
        }

        return $operands;
    }

    /**
     * The number of character codes eligible for word spacing (Tw): the single-byte character code 32. Composite
     * (Type0) fonts encode space as a byte inside a multi-byte code, to which Tw does not apply (spec §9.3.3).
     */
    private function getWordSpaceableCharacterCount(Font $font): int {
        if ($font->getDescendantFonts() !== []) {
            return 0;
        }

        return count(array_filter($this->getCodePoints(), static fn(int $codePoint): bool => $codePoint === 32));
    }
}
