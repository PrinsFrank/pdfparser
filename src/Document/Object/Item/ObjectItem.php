<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

interface ObjectItem {
    /** @throws PdfParserException */
    public function getDictionary(Stream $stream): Dictionary;
}
