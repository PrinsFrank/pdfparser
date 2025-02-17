<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Item;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Exception\PdfParserException;

interface ObjectItem {
    /** @throws PdfParserException */
    public function getDictionary(Document $document): Dictionary;

    public function getContent(Document $document): string;
}
