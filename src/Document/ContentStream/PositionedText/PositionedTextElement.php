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

        $string = '';
        foreach ($this->textSegments as $textSegment) {
            $string .= $textSegment->getText($differences, $encoding, $toUnicodeCMap);
            if ($textSegment->offset !== null
                && $textSegment->offset / 1000 <= -self::WORD_BREAK_THRESHOLD_EM) {
                $string .= ' ';
            }
        }

        return $string;
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
        return ($this->textState->fontSize ?? 12)
            * abs($this->absoluteMatrix->scaleY)
            * ($this->textState->scale / 100);
    }
}
