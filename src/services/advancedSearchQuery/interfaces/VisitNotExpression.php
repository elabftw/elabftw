<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\NotExpression;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitNotExpression
{
    public function visitNotExpression(NotExpression $not_expression, VisitorParameters $parameters): WhereCollector|int;
}
