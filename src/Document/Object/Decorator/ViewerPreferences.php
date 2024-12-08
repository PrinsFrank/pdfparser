<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;

class ViewerPreferences extends DecoratedObject {
    #[Override]
    protected function getTypeName(): ?TypeNameValue {
        return TypeNameValue::VIEWER_PREFERENCES;
    }
}
