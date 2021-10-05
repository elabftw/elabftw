<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

class VisitorParameters
{
    public function __construct(private string $column)
    {
    }

    public function getColumn(): string
    {
        return $this->column;
    }
}
