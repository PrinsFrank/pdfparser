<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

/** @internal */
enum GraphicsStateOperator: string implements InteractsWithTextMatrix {
    case SaveCurrentStateToStack = 'q';
    case RestoreMostRecentStateFromStack = 'Q';
    case ModifyCurrentTransformationMatrix = 'cm';
    case SetLineWidth = 'w';
    case SetLineCap = 'J';
    case SetLineJoin = 'j';
    case SetMiterJoin = 'M';
    case SetLineDash = 'd';
    case SetIntent = 'ri';
    case SetFlatness = 'i';
    case SetDictName = 'gs';

    public function applyToTextMatrix(string $operands, TransformationMatrix $transformationMatrix): TransformationMatrix
    {
        // TODO: Implement applyToTextMatrix() method.
    }
}
