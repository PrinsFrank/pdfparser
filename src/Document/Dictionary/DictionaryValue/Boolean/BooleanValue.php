<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
class BooleanValue implements DictionaryValue {
    public function __construct(
        public readonly bool $value,
    ) {}

    /**
     * ISO 32000-2:2020, 7.3.2 defines booleans as the keywords true/false.
     * ISO 32000-2:2020, 7.3.5 defines /true and /false as name objects.
     * This parser also accepts slash-prefixed forms as a non-standard recovery path for malformed PDFs.
     */
    #[Override]
    public static function fromValue(string $valueString): ?self {
        if ($valueString === 'true' || $valueString === '/true') {
            return new self(true);
        }

        if ($valueString === 'false' || $valueString === '/false') {
            return new self(false);
        }

        return null;
    }
}
