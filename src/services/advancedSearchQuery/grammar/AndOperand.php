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

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AndOperand implements Visitable
{
    public function __construct(private SimpleValueWrapper | DateField | TimestampField | MetadataField | Field | NotExpression | OrExpression $operand, private ?self $tail = null) {}

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitAndOperand($this, $parameters);
    }

    public function getOperand(): SimpleValueWrapper | DateField | TimestampField | MetadataField | Field | NotExpression | OrExpression
    {
        return $this->operand;
    }

    public function getTail(): ?self
    {
        return $this->tail;
    }
}
