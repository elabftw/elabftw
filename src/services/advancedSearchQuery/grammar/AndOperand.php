<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AndOperand implements Visitable
{
    public function __construct(private SimpleValueWrapper|NotExpression|OrExpression $operand, private ?self $tail = null)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitAndOperand($this, $parameters);
    }

    public function getOperand(): SimpleValueWrapper|NotExpression|OrExpression
    {
        return $this->operand;
    }

    public function getTail(): ?self
    {
        return $this->tail;
    }
}
