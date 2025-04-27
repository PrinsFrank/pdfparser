<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;

interface InteractsWithTextState {
    public function applyToTextState(string $operands, ?TextState $textState): ?TextState;
}
