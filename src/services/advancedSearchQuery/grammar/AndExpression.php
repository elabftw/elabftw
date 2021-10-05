<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AndExpression implements Visitable
{
    public function __construct(private SimpleValueWrapper|NotExpression|OrExpression $expression, private ?AndOperand $tail = null)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitAndExpression($this, $parameters);
    }

    public function getExpression(): SimpleValueWrapper|NotExpression|OrExpression
    {
        return $this->expression;
    }

    public function getTail(): ?AndOperand
    {
        return $this->tail;
    }
}
