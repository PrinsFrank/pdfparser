<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Unused\Object\ObjectItem;

class ObjectItemCollection {
    /** @var array<ObjectItem> */
    public readonly array $objectItems;

    public function __construct(
        ObjectItem... $objectItems
    ) {
        $this->objectItems = $objectItems;
    }
}