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

class AndExpression implements Visitable
{
    public function __construct(private SimpleValueWrapper | DateField | TimestampField | MetadataField | Field | NotExpression | OrExpression $expression, private ?AndOperand $tail = null) {}

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitAndExpression($this, $parameters);
    }

    public function getExpression(): SimpleValueWrapper | DateField | TimestampField | MetadataField | Field | NotExpression | OrExpression
    {
        return $this->expression;
    }

    public function getTail(): ?AndOperand
    {
        return $this->tail;
    }
}
