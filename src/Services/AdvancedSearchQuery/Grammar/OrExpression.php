<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Override;

final class OrExpression implements Visitable
{
    public function __construct(private AndExpression $expression, private ?OrOperand $tail = null) {}

    #[Override]
    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitOrExpression($this, $parameters);
    }

    public function getExpression(): AndExpression
    {
        return $this->expression;
    }

    public function getTail(): ?OrOperand
    {
        return $this->tail;
    }
}
