<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Stream;

interface ObjectItem {
    public function getDictionary(Stream $stream): Dictionary;
}
