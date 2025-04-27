<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextShowingOperator;

#[CoversClass(TextObject::class)]
class TextObjectTest extends TestCase {
    public function testIsEmpty(): void {
        $textObject = new TextObject();
        static::assertTrue($textObject->isEmpty());

        $textObject->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, ''));
        static::assertFalse($textObject->isEmpty());
    }
}
