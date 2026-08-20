<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\IncludesXObjects;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Decorator\XObject;

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
            ?->getValueForKey($context->document, ExtendedDictionaryKey::fromKeyString($operands), ReferenceValue::class);
        if ($reference === null) {
            return [];
        }

        $xObject = $context->document->getObject($reference->objectNumber, XObject::class);
        if ($xObject === null || $xObject->isForm() === false) {
            return [];
        }

        if (in_array($xObject->objectNumber, $visitedObjectIds, true)) {
            return [];
        }

        $visitedObjectIds[] = $xObject->objectNumber;
        return $xObject->getPositionedTextElements($transformationMatrix, $visitedObjectIds);
    }
}
