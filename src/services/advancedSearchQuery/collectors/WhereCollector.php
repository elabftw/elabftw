<?php declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Collectors;

class WhereCollector
{
    public function __construct(private string $where, private array $bindValues)
    {
    }

    public function getWhere(): string
    {
        return $this->where;
    }

    public function getBindValues(): array
    {
        return $this->bindValues;
    }
}
