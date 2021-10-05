<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndExpression;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitAndExpression
{
    public function visitAndExpression(AndExpression $andExpression, VisitorParameters $parameters): WhereCollector|int;
}
