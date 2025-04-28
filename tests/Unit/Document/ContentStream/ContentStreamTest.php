<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;

#[CoversClass(ContentStream::class)]
class ContentStreamTest extends TestCase {
    public function testGetPositionedTextElements(): void {
        $textObjectString = <<<EOD
            1 0 0 -1 0 842 cm
            q
            .75 0 0 .75 0 0 cm
            1 1 1 RG 1 1 1 rg
            /G3 gs
            0 0 794 1123 re
            f
            Q
            q
            .75 0 0 .75 72 72 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            /P <</MCID 0 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0024> Tj
            9.7756042 0 Td <0025> Tj
            9.7756042 0 Td <0026> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            30.135483 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 86.546265 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 1 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0027> Tj
            10.5842743 0 Td <0028> Tj
            9.7756042 0 Td <0029> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            29.3125 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 101.092529 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 2 >>BDC
            BT
            /Span<</ActualText <FEFF200B> >> BDC
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0003> Tj
            EMC
            ET
            BT
            /Span<</ActualText <FEFF200B> >> BDC
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            48 -13.2773438 Td <0003> Tj
            EMC
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            96 -13.2773438 Td <002A> Tj
            11.4001007 0 Td <002B> Tj
            10.5842743 0 Td <002C> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            122.056351 -13.2773438 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            4.0719757 0 Td <0003> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            146.488205 -13.2773438 Td <002D> Tj
            7.328125 0 Td <002E> Tj
            9.7756042 0 Td <002F> Tj
            ET
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            171.74304 -13.2773438 Td <0003> Tj
            ET
            Q
            q
            .75 0 0 .75 72 115.638794 cm
            0 0 0 RG 0 0 0 rg
            /G3 gs
            EMC
            /P <</MCID 3 >>BDC
            BT
            /F4 14.666667 Tf
            1 0 0 -1 0 .47981739 Tm
            0 -13.2773438 Td <0003> Tj
            ET
            Q
            EMC
        EOD;
        static::assertEquals(
            [
                new PositionedTextElement('<0024>', new TransformationMatrix(1, 0, 0, -1, 0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0025>', new TransformationMatrix(1, 0, 0, -1, 9.7756042, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0026>', new TransformationMatrix(1, 0, 0, -1, 19.5512084, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 30.135483, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0027>', new TransformationMatrix(1, 0, 0, -1, 0.0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0028>', new TransformationMatrix(1, 0, 0, -1, 10.5842743, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0029>', new TransformationMatrix(1, 0, 0, -1, 20.3598785, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 29.3125, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 0.0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 48.0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002A>', new TransformationMatrix(1, 0, 0, -1, 96.0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002B>', new TransformationMatrix(1, 0, 0, -1, 107.4001007, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002C>', new TransformationMatrix(1, 0, 0, -1, 117.984375, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 122.056351, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 126.1283267, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 130.2003024, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 134.2722781, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 138.3442538, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 142.4162295, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002D>', new TransformationMatrix(1, 0, 0, -1, 146.488205, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002E>', new TransformationMatrix(1, 0, 0, -1, 153.81633, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<002F>', new TransformationMatrix(1, 0, 0, -1, 163.5919342, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 171.74304, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
                new PositionedTextElement('<0003>', new TransformationMatrix(1, 0, 0, -1, 0.0, -12.79752641), new TextState(new ExtendedDictionaryKey('F4'), 14.666667)),
            ],
            ContentStreamParser::parse($textObjectString)->getPositionedTextElements(),
        );
    }
}
