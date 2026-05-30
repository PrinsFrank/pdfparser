<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\CMap\Registry\Adobe\Identity0;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;

enum ToUnicodeCMapNameValue: string implements NameValue {
    case IdentityV = 'Identity-V';
    case IdentityH = 'Identity-H';

    public function getToUnicodeCMap(): ToUnicodeCMap {
        return (new Identity0())
            ->getToUnicodeCMap();
    }
}
