<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;

interface InteractsWithColorState {
    public function applyToColorState(string $operands, ColorState $colorState): ColorState;
}
