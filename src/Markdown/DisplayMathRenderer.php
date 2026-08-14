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

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use Override;
use Stringable;

final class DisplayMathRenderer implements NodeRendererInterface
{
    #[Override]
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): Stringable
    {
        DisplayMath::assertInstanceOf($node);
        /** @var DisplayMath $node */
        return new HtmlElement('div', array(), Xml::escape($node->getLiteral()));
    }
}
