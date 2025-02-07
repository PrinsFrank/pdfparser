<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

class TextObject {
    /** @var list<TextOperator> */
    public array $textOperators = [];

    public function addTextOperator(TextOperator $textOperator): self {
        $this->textOperators[] = $textOperator;

        return $this;
    }

    public function isEmpty(): bool {
        return $this->textOperators === [];
    }
}
