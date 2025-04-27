<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

/** @internal */
class TextObject {
    /** @var list<ContentStreamCommand> */
    public array $commands = [];

    public function addCommand(ContentStreamCommand $textOperator): self {
        $this->commands[] = $textOperator;

        return $this;
    }

    public function isEmpty(): bool {
        return $this->commands === [];
    }
}
