<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\IncludesXObjects;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Page;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\XObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/**
 * @internal
 *
 * @specification table 86 - XObject operator
 */
enum XObjectOperator: string implements IncludesXObjects {
    case Paint = 'Do';

    /** @param list<int> $visitedObjectIds */
    #[Override]
    public function getPositionedTextElements(string $operands, TransformationMatrix $transformationMatrix, Page|XObject $context, array $visitedObjectIds): array {
        $reference = $context->getXObjectsDictionary()
            ?->getValueForKey(NameObjectStringValue::fromValue($operands) ?? throw new ParseFailureException(), ReferenceValue::class);
        if ($reference === null) {
            return [];
        }

        $xObject = $context->dictionary->object?->document->getObject($reference->objectNumber);
        if ($xObject === null || ($xObjectDictionary = $xObject->getDictionary(XObject::class))->isForm() === false) {
            return [];
        }

        if (in_array($xObject->objectItem->objectNumber, $visitedObjectIds, true)) {
            return [];
        }

        $visitedObjectIds[] = $xObject->objectItem->objectNumber;
        return $xObjectDictionary->getPositionedTextElements($transformationMatrix, $visitedObjectIds);
    }
}
