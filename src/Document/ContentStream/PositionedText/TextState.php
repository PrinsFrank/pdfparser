<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;

readonly class TextState {
    /** Assumed font size (Tfs) when a content stream shows text without ever setting one; the PDF spec defines no default. */
    private const DEFAULT_FONT_SIZE = 10.0;

    public function __construct(
        public DictionaryKey|ExtendedDictionaryKey|null $fontName, // Tf
        public ?float $fontSize, // Tfs
        public float $charSpace = 0,      // Tc
        public float $wordSpace = 0,      // Tw
        public float $scale = 100,        // Th
        public float $leading = 0,        // Tl
        public int $render = 0,           // Tmode
        public float $rise = 0,           // Trise
    ) {}

    /** The effective font size (Tfs), falling back to an assumed default when the content stream never set one. */
    public function getFontSize(): float {
        return $this->fontSize ?? self::DEFAULT_FONT_SIZE;
    }

    public function withFont(DictionaryKey|ExtendedDictionaryKey|null $fontName, ?float $fontSize): self {
        return new TextState(
            $fontName,
            $fontSize,
            $this->charSpace,
            $this->wordSpace,
            $this->scale,
            $this->leading,
            $this->render,
            $this->rise,
        );
    }

    public function withCharSpace(float $charSpace): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $charSpace,
            $this->wordSpace,
            $this->scale,
            $this->leading,
            $this->render,
            $this->rise,
        );
    }

    public function withWordSpace(float $wordSpace): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $this->charSpace,
            $wordSpace,
            $this->scale,
            $this->leading,
            $this->render,
            $this->rise,
        );
    }

    public function withScale(float $scale): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $this->charSpace,
            $this->wordSpace,
            $scale,
            $this->leading,
            $this->render,
            $this->rise,
        );
    }

    public function withLeading(float $leading): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $this->charSpace,
            $this->wordSpace,
            $this->scale,
            $leading,
            $this->render,
            $this->rise,
        );
    }

    public function withRender(int $render): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $this->charSpace,
            $this->wordSpace,
            $this->scale,
            $this->leading,
            $render,
            $this->rise,
        );
    }

    public function withRise(float $rise): self {
        return new TextState(
            $this->fontName,
            $this->fontSize,
            $this->charSpace,
            $this->wordSpace,
            $this->scale,
            $this->leading,
            $this->render,
            $rise,
        );
    }
}
