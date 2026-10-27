<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object;

use PrinsFrank\PdfParser\Document\Dictionary\Decorator\DecoratedDictionary;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\GenericDictionary;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\Stream;

interface ObjectItem {
    /**
     * @template T of DecoratedDictionary
     * @param class-string<T> $decoratorFQN
     * @throws PdfParserException
     * @return T
     */
    public function getDictionary(string $decoratorFQN = GenericDictionary::class): DecoratedDictionary;

    public function getContent(): Stream;
}
