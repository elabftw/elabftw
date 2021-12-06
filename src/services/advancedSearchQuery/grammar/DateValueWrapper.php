<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\DateTerm;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use function filter_var;
use function trim;

class DateValueWrapper implements DateTerm, Visitable
{
    public function __construct(private array $dateArr)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitDateValueWrapper($this, $parameters);
    }

    public function getValue(): string
    {
        return filter_var(trim($this->dateArr['date']), FILTER_SANITIZE_STRING) ?: '';
    }

    public function getDateTo(): string
    {
        return filter_var(trim($this->dateArr['dateTo']), FILTER_SANITIZE_STRING) ?: '';
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
