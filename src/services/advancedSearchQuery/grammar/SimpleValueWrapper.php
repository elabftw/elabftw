<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use function filter_var;
use function trim;

class SimpleValueWrapper implements Term, Visitable
{
    public function __construct(private string $value)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitSimpleValueWrapper($this, $parameters);
    }

    public function getValue(): string
    {
        return filter_var(trim($this->value), FILTER_SANITIZE_STRING) ?: '';
    }
}
