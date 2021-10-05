<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitOrExpression
{
    public function visitOrExpression(OrExpression $orExpression, VisitorParameters $parameters): WhereCollector|int;
}
