<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\Registry\Adobe;

use Override;
use PrinsFrank\PdfParser\Document\CMap\Registry\CMapResource;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFRange;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;

class Identity0 implements CMapResource {
    #[Override]
    public function getToUnicodeCMap(): ToUnicodeCMap {
        return new ToUnicodeCMap(
            0x0000,
            0xFFFF,
            2,
            new BFRange(0x0000, 0xFFFF, ['0000'])
        );
    }
}
