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

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Override;

use function in_array;
use function trim;

final class DisplayMathStartParser implements BlockStartParserInterface
{
    #[Override]
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || !in_array($cursor->getNextNonSpaceCharacter(), array('$', '\\'), true)) {
            return BlockStart::none();
        }
        $opening = $cursor->match('/^[ \t]*(?:\$\$|\\\\\[)[ \t]*$/');
        if ($opening === null) {
            return BlockStart::none();
        }
        $opening = trim($opening);
        $closing = $opening === '$$' ? '$$' : '\\]';
        return BlockStart::of(new DisplayMathParser($opening, $closing))->at($cursor);
    }
}
