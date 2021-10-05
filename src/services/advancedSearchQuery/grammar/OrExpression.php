<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class OrExpression implements Visitable
{
    public function __construct(private AndExpression $expression, private null|OrOperand $tail = null)
    {
    }

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
