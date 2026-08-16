<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\StringContainerInterface;
use Override;

final class DisplayMath extends AbstractBlock implements StringContainerInterface
{
    private string $literal = '';

    #[Override]
    public function getLiteral(): string
    {
        return $this->literal;
    }

    #[Override]
    public function setLiteral(string $literal): void
    {
        $this->literal = $literal;
    }
}
