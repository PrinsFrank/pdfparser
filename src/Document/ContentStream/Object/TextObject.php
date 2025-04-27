<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Object;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;

/** @internal */
class TextObject {
    /** @var list<ContentStreamCommand> */
    public array $contentStreamCommands = [];

    public function addContentStreamCommand(ContentStreamCommand $textOperator): self {
        $this->contentStreamCommands[] = $textOperator;

        return $this;
    }

    public function isEmpty(): bool {
        return $this->contentStreamCommands === [];
    }
}
