<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Page;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Object\ObjectItem;
use PrinsFrank\PdfParser\Document\Object\ObjectStream\ObjectStream;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

class Page
{
    private readonly ObjectItem|ObjectStream      $pageObject;

    /** @var list<ObjectItem|ObjectStream> */
    private readonly array  $contentObjects;

    /** @throws InvalidArgumentException */
    public function __construct(ObjectItem|ObjectStream $pageObject, ObjectItem|ObjectStream... $contentObjects)
    {
        if ($pageObject->dictionary->getEntryWithKey(DictionaryKey::TYPE)?->value !== TypeNameValue::PAGE) {
            throw new InvalidArgumentException();
        }

        $this->pageObject    = $pageObject;
        $this->contentObjects = $contentObjects;
    }
}
