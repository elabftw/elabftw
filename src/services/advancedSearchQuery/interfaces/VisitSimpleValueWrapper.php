<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SimpleValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitSimpleValueWrapper
{
    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector|int;
}
