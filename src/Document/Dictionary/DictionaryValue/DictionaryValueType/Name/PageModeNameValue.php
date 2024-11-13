<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Name;

use Override;

enum PageModeNameValue: string implements NameValue {
    case USE_NONE = 'UseNone';
    case USE_OUTLINES = 'UseOutlines';
    case USE_THUMBS = 'UseThumbs';
    case FULL_SCREEN = 'FullScreen';
    case USE_O_C = 'UseOC';
    case USE_ATTACHMENTS = 'UseAttachments';

    #[Override]
    public static function fromValue(string $valueString): self {
        return self::from(trim(ltrim($valueString, '/')));
    }
}
