<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Deprecated;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Item\ObjectItem;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
abstract class DecoratedObject {
    final public function __construct(
        public readonly ObjectItem $objectItem,
        public readonly Document $document
    ) {
        $typeNameValue = $this->objectItem->getDictionary($document)->getType();
        if ($typeNameValue !== null && !in_array($typeNameValue->getDecoratorFQN(), [static::class, GenericObject::class], true)) {
            throw new InvalidArgumentException(
                sprintf('Object should have decorator %s, got %s', $typeNameValue->getDecoratorFQN(), static::class)
            );
        }
    }

    /** @throws PdfParserException */
    public function getDictionary(): Dictionary {
        return $this->objectItem->getDictionary($this->document);
    }

    public function getStream(): Stream {
        return $this->objectItem->getContent($this->document);
    }

    #[Deprecated('Use self::getStream() instead')]
    public function getContent(): string {
        return $this->getStream()->toString();
    }
}
