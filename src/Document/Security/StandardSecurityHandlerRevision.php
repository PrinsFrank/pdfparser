<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Security;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;

enum StandardSecurityHandlerRevision: string implements NameValue {
    case v2 = '2';
    case v3 = '3';
    case v4 = '4';
    case v5 = '5';
    case v6 = '6';
}
