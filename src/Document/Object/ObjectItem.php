<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;

class ObjectItem {
    public function __construct(
        public int        $objectNumber,
        public string     $content,
        public Dictionary $dictionary
    ) {
    }
}
