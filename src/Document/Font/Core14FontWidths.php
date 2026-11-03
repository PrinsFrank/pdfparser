<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Font;

use PrinsFrank\Core14FontMetrics\Courier;
use PrinsFrank\Core14FontMetrics\CourierBold;
use PrinsFrank\Core14FontMetrics\CourierBoldOblique;
use PrinsFrank\Core14FontMetrics\CourierOblique;
use PrinsFrank\Core14FontMetrics\Font;
use PrinsFrank\Core14FontMetrics\Helvetica;
use PrinsFrank\Core14FontMetrics\HelveticaBold;
use PrinsFrank\Core14FontMetrics\HelveticaBoldOblique;
use PrinsFrank\Core14FontMetrics\HelveticaOblique;
use PrinsFrank\Core14FontMetrics\Symbol;
use PrinsFrank\Core14FontMetrics\TimesBold;
use PrinsFrank\Core14FontMetrics\TimesBoldItalic;
use PrinsFrank\Core14FontMetrics\TimesItalic;
use PrinsFrank\Core14FontMetrics\ZapfDingbats;

readonly class Core14FontWidths {
    /** @param class-string<Font> $fontFQN */
    public function __construct(
        private string $fontFQN,
    ) {}

    public function getWidthForCharacter(int $characterCode): ?float {
        return ($this->fontFQN)::fromCharacterCode($characterCode)
            ?->getWidth();
    }

    public static function fromBaseFont(string $baseFont): ?self {
        return match (strtolower(str_replace('-', '', $baseFont))) {
            'courier' => new self(Courier::class),
            'courierbold' => new self(CourierBold::class),
            'courierboldoblique' => new self(CourierBoldOblique::class),
            'courieroblique' => new self(CourierOblique::class),
            'helvetica' => new self(Helvetica::class),
            'helveticabold' => new self(HelveticaBold::class),
            'helveticaboldoblique' => new self(HelveticaBoldOblique::class),
            'helveticaoblique' => new self(HelveticaOblique::class),
            'symbol' => new self(Symbol::class),
            'timesbold' => new self(TimesBold::class),
            'timesbolditalic' => new self(TimesBoldItalic::class),
            'timesitalic' => new self(TimesItalic::class),
            'zapfdingbats' => new self(ZapfDingbats::class),
            default => null,
        };
    }
}
