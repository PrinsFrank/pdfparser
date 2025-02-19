<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\TextObject;
use PrinsFrank\PdfParser\Document\Text\TextOperator;

#[CoversClass(TextObject::class)]
class TextObjectTest extends TestCase {
    public function testIsEmpty(): void {
        $textObject = new TextObject();
        static::assertTrue($textObject->isEmpty());

        $textObject->addTextOperator(new TextOperator(TextShowingOperator::SHOW, ''));
        static::assertFalse($textObject->isEmpty());
    }
}
