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

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use Override;

use function implode;
use function trim;
use function array_slice;

final class DisplayMathParser extends AbstractBlockContinueParser
{
    private DisplayMath $block;

    /** @var array<int, string> */
    private array $lines = array();

    private bool $closed = false;

    public function __construct(private string $opening, private string $closing)
    {
        $this->block = new DisplayMath();
    }

    #[Override]
    public function getBlock(): DisplayMath
    {
        return $this->block;
    }

    #[Override]
    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): BlockContinue
    {
        if (!$cursor->isIndented() && trim($cursor->getRemainder()) === $this->closing) {
            $this->closed = true;
            return BlockContinue::finished();
        }
        return BlockContinue::at($cursor);
    }

    #[Override]
    public function addLine(string $line): void
    {
        $this->lines[] = $line;
    }

    #[Override]
    public function closeBlock(): void
    {
        // CommonMark adds the empty remainder of the consumed opening line first.
        $lines = array($this->opening, ...array_slice($this->lines, 1));
        if ($this->closed) {
            $lines[] = $this->closing;
        }
        $this->block->setLiteral(implode("\n", $lines));
    }
}
