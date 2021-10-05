<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class NotExpression implements Visitable
{
    public function __construct(private SimpleValueWrapper|OrExpression $expression)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitNotExpression($this, $parameters);
    }

    public function getExpression(): SimpleValueWrapper|OrExpression
    {
        return $this->expression;
    }
}
