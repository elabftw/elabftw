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

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Override;

final class DateField implements Term, Visitable
{
    public function __construct(private array $dateArr) {}

    #[Override]
    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitDateField($this, $parameters);
    }

    #[Override]
    public function getValue(): string
    {
        return $this->dateArr['date'] ?: '';
    }

    public function getDateTo(): string
    {
        return $this->dateArr['dateTo'] ?: '';
    }

    public function getDateType(): string
    {
        return $this->dateArr['type'];
    }

    public function getOperator(): string
    {
        return $this->dateArr['operator'] ?: '=';
    }
}
