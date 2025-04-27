<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
enum GraphicsStateOperator: string implements InteractsWithTransformationMatrix {
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

    /** @throws ParseFailureException */
    public function applyToTransformationMatrix(string $operands, TransformationMatrix $transformationMatrix): TransformationMatrix {
        if ($this === self::ModifyCurrentTransformationMatrix) {
            $matrix = explode(' ', trim($operands));
            if (count($matrix) !== 6) {
                throw new ParseFailureException();
            }

            return $transformationMatrix
                ->multiplyWith(
                    new TransformationMatrix(
                        (float) $matrix[0],
                        (float) $matrix[1],
                        (float) $matrix[2],
                        (float) $matrix[3],
                        (float) $matrix[4],
                        (float) $matrix[5],
                    )
                );
        }

        return $transformationMatrix;
    }
}
