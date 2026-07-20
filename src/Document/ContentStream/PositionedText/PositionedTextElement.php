<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class PositionedTextElement {
    public const WORD_BREAK_THRESHOLD_EM = 0.25;

    /** @param list<TextSegment> $textSegments */
    public function __construct(
        public array $textSegments,
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
        $font = $this->getFont($document, $page);
        $differences = $font->getDifferences();
        $encoding = $font->getEncoding();
        $toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont();

        $text = '';
        $previousOffset = null;
        foreach ($this->textSegments as $textSegment) {
            $textSegmentText = $textSegment->getText($differences, $encoding, $toUnicodeCMap);
            if ($previousOffset !== null
                && $previousOffset / 1000 <= -self::WORD_BREAK_THRESHOLD_EM
                && str_ends_with($text, ' ') === false
                && str_starts_with($textSegmentText, ' ') === false) {
                $text .= ' ';
            }

            $text .= $textSegmentText;
            $previousOffset = $textSegment->offset;
        }

        return $text;
    }

    /** @return list<int> */
    public function getCodePoints(): array {
        $codePoints = [];
        foreach ($this->textSegments as $textSegment) {
            array_push($codePoints, ...$textSegment->getCodePoints());
        }

        return $codePoints;
    }

    public function getHeight(): float {
        return ($this->textState->getFontSize())
            * abs($this->absoluteMatrix->scaleY)
            * ($this->textState->scale / 100);
    }

    /**
     * The horizontal distance, in device space, that showing this element advances the text cursor, per the
     * displacement formula in the PDF spec §9.4.4:
     *
     *   ((w0 − Tj/1000)·Tfs + Tc + Tw·[single-byte code 32]) · Th , transformed by the text rendering matrix.
     *
     * Reconstructed here because Tj/TJ do not advance the text matrix in this parser.
     */
    public function getAdvanceWidth(Document $document, Page $page): float {
        $font = $this->getFont($document, $page);
        $scaleX = $this->absoluteMatrix->scaleX;
        $fontSize = $this->textState->getFontSize();

        $glyphAdvance = $font->getWidthForChars($this->getCodePoints(), $this->textState, $this->absoluteMatrix); // Σ (w0·Tfs + Tc + Tw·[code 32]) · scaleX
        $offsetAdvance = -($this->getTotalOffset() / 1000) * $fontSize * $scaleX;                                 // − Σ(Tj)/1000 · Tfs · scaleX

        return ($glyphAdvance + $offsetAdvance) * ($this->textState->scale / 100); // · Th
    }

    /** The sum of the TJ adjustment numbers in this element's segments, in thousandths of an em. */
    public function getTotalOffset(): float {
        $totalOffset = 0.0;
        foreach ($this->textSegments as $textSegment) {
            if ($textSegment->offset !== null) {
                $totalOffset += $textSegment->offset;
            }
        }

        return $totalOffset;
    }
}
