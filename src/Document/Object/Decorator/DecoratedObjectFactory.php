<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Item\ObjectItem;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class DecoratedObjectFactory {
    /**
     * @template T of DecoratedObject
     * @param class-string<T>|null $expectedDecoratorFQN
     * @return ($expectedDecoratorFQN is null ? DecoratedObject : T)
     */
    public static function forItem(?ObjectItem $objectItem, Document $document, ?string $expectedDecoratorFQN): ?DecoratedObject {
        if ($objectItem === null) {
            return null;
        }

        $typeNameValue = $objectItem->getDictionary($document->stream)->getValueForKey(DictionaryKey::TYPE, TypeNameValue::class);
        if ($expectedDecoratorFQN !== null && $typeNameValue !== null && $expectedDecoratorFQN !== $typeNameValue->getDecoratorFQN()) {
            throw new ParseFailureException(sprintf('Expected object of type %s, got %s', $expectedDecoratorFQN, $typeNameValue->getDecoratorFQN()));
        }

        $decoratorFQN = $expectedDecoratorFQN
            ?? $typeNameValue?->getDecoratorFQN()
            ?? GenericObject::class;

        return new $decoratorFQN($objectItem, $document);
    }
}
