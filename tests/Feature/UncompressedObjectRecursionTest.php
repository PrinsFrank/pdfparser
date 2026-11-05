<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\PdfParser;

#[CoversNothing]
class UncompressedObjectRecursionTest extends TestCase {
    public function testThrowsExceptionWhenEncounteringUncompressedObjectRecursion(): void {
        $document = (new PdfParser())
            ->parseFile(__DIR__ . '/samples/uncompressed-object-recursion.pdf');

        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('Invalid crossReference entry, an object cannot contain itself');
        $document->getText();
    }
}
