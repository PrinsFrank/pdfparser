<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Decorator\DecoratedDictionary;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\GenericDictionary;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
abstract class DecoratedObject {
    final public function __construct(
        public readonly CompressedObject|UncompressedObject $objectItem,
    ) {
        $typeNameValue = $this->objectItem->getDictionary()->getType();
        if ($typeNameValue !== null && !in_array($typeNameValue->getDecoratorFQN(), [static::class, GenericObject::class], true)) {
            throw new InvalidArgumentException(
                sprintf('Object should have decorator %s, got %s', $typeNameValue->getDecoratorFQN(), static::class),
            );
        }
    }

    /**
     * @template T of DecoratedDictionary
     * @param class-string<T> $decoratorFQN
     * @throws PdfParserException
     * @return T
     */
    public function getDictionary(string $decoratorFQN = GenericDictionary::class): DecoratedDictionary {
        return $this->objectItem->getDictionary($decoratorFQN);
    }

    public function getStream(): Stream {
        return $this->objectItem->getContent();
    }
}
