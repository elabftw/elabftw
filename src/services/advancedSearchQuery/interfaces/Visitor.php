<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

interface Visitor extends VisitOrExpression, VisitOrOperand, VisitAndExpression, VisitAndOperand, VisitNotExpression, VisitSimpleValueWrapper
{
}
