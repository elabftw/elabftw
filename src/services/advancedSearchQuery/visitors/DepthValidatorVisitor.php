<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

use Elabftw\Services\AdvancedSearchQuery\Exceptions\LimitDepthIsExceededException;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\NotExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SimpleValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;

class DepthValidatorVisitor implements Visitor
{
    public function __construct(private ?int $limit = null)
    {
    }

    public function checkDepthOfTree(Visitable $parsedQuery, string $column): void
    {
        $parsedQuery->accept($this, new VisitorParameters($column));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): int
    {
        return 1;
    }

    public function visitNotExpression(NotExpression $notExpression, VisitorParameters $parameters): int
    {
        $depth = $notExpression->getExpression()->accept($this, $parameters);
        $depth += 1;

        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    public function visitAndExpression(AndExpression $andExpression, VisitorParameters $parameters): int
    {
        return $this->visitExpression($andExpression, $parameters);
    }

    public function visitAndOperand(AndOperand $andOperand, VisitorParameters $parameters): int
    {
        return $this->visitOperand($andOperand, $parameters);
    }

    public function visitOrExpression(OrExpression $orExpression, VisitorParameters $parameters): int
    {
        return $this->visitExpression($orExpression, $parameters);
    }

    public function visitOrOperand(OrOperand $orOperand, VisitorParameters $parameters): int
    {
        return $this->visitOperand($orOperand, $parameters);
    }

    private function visitTail(OrExpression|AndExpression|OrOperand|AndOperand $tail = null, VisitorParameters $parameters): int
    {
        if ($tail) {
            return $tail->accept($this, $parameters);
        }
        return 0;
    }

    private function visitOperand(OrOperand|AndOperand $operand, VisitorParameters $parameters): int
    {
        $left = $operand->getOperand()->accept($this, $parameters);
        $right = $this->visitTail($operand->getTail(), $parameters);

        $depth = $left > $right ? $left + 1 : $right + 1;
        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    private function visitExpression(OrExpression|AndExpression $expression, VisitorParameters $parameters): int
    {
        $left = $expression->getExpression()->accept($this, $parameters);
        $right = $this->visitTail($expression->getTail(), $parameters);

        $depth = $left > $right ? $left + 1 : $right + 1;
        if ($this->isDepthExceed($depth)) {
            throw new LimitDepthIsExceededException();
        }

        return $depth;
    }

    private function isDepthExceed(int $depth): bool
    {
        if (!$this->limit) {
            return false;
        }
//        error_log(print_r($depth, true));
        return $depth > $this->limit;
    }
}
