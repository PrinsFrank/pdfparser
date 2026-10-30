<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\Stream;

/** @mixin Dictionary */
abstract readonly class DecoratedDictionary {
    final public function __construct(
        public Dictionary $dictionary,
    ) {}

    /** @param list<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed {
        // @phpstan-ignore method.dynamicName
        return $this->dictionary->$name(...$arguments);
    }

    public function getContent(): Stream {
        return $this->dictionary->object?->getContent()
            ?? throw new ParseFailureException();
    }
}
