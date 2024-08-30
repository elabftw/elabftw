<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Collectors;

class WhereCollector
{
    public function __construct(private string $where, private array $bindValues) {}

    public function getWhere(): string
    {
        return $this->where;
    }

    public function getBindValues(): array
    {
        return $this->bindValues;
    }
}
