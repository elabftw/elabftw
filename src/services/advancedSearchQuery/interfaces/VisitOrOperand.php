<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrOperand;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitOrOperand
{
    public function visitOrOperand(OrOperand $orOperand, VisitorParameters $parameters): WhereCollector|int;
}
