<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Name;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;

#[CoversClass(FilterNameValue::class)]
class FilterNameValueTest extends TestCase {
    #[DataProvider('cases')]
    public function testGetImageTypeCanBeCalledForAllFilterNameValues(FilterNameValue $filterNameValue): void {
        /** @phpstan-ignore method.resultUnused */
        $filterNameValue->getImageType();
    }

    /** @return iterable<array{0: FilterNameValue}> */
    public static function cases(): iterable {
        foreach (FilterNameValue::cases() as $filterNameValue) {
            yield [$filterNameValue];
        }
    }
}
