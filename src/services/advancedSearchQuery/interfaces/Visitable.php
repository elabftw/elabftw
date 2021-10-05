<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface Visitable
{
    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed;
}
