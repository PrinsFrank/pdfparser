<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\OperatorString;

/** @internal */
enum GraphicsStateOperator: string {
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
}
