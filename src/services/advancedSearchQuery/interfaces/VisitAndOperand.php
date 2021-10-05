<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndOperand;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitAndOperand
{
    public function visitAndOperand(AndOperand $andOperand, VisitorParameters $parameters): WhereCollector|int;
}
