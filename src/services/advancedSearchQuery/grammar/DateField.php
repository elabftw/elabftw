<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use function filter_var;

class DateField implements Term, Visitable
{
    public function __construct(private array $dateArr)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitDateField($this, $parameters);
    }

    public function getValue(): string
    {
        return filter_var($this->dateArr['date'], FILTER_SANITIZE_STRING) ?: '';
    }

    public function getDateTo(): string
    {
        return filter_var($this->dateArr['dateTo'], FILTER_SANITIZE_STRING) ?: '';
    }

    public function getDateType(): string
    {
        return $this->dateArr['type'];
    }

    public function getOperator(): string
    {
        return ' ' . ($this->dateArr['operator'] ?: '=') . ' ';
    }
}
