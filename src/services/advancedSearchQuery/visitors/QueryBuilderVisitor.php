<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

use function array_merge;
use function bin2hex;
use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\NotExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SimpleValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use PDO;
use function random_bytes;

class QueryBuilderVisitor implements Visitor
{
    public function buildWhere(Visitable $parsedQuery, string $column): WhereCollector
    {
        return $parsedQuery->accept($this, new VisitorParameters($column));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $value = $simpleValueWrapper->getValue();
        $param = ':' . bin2hex(random_bytes(5));

        return new WhereCollector(
            'entity.' . $parameters->getColumn() . ' LIKE ' . $param,
            array(array('param' => $param, 'value' => '%' . $value . '%', 'type' => PDO::PARAM_STR)),
        );
    }

    public function visitNotExpression(NotExpression $notExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $notExpression->getExpression()->accept($this, $parameters);

        return new WhereCollector(
            'NOT (' . $WhereCollectorExpression->getWhere() . ')',
            $WhereCollectorExpression->getBindValues(),
        );
    }

    public function visitAndExpression(AndExpression $andExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $andExpression->getExpression()->accept($this, $parameters);

        $tail = $andExpression->getTail();

        return $this->buildAndClause($tail, $WhereCollectorExpression, $parameters);
    }

    public function visitOrExpression(OrExpression $orExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $orExpression->getExpression()->accept($this, $parameters);

        $tail = $orExpression->getTail();

        return $this->buildOrClause($tail, $WhereCollectorExpression, $parameters);
    }

    public function visitOrOperand(OrOperand $orOperand, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $orOperand->getOperand()->accept($this, $parameters);

        $tail = $orOperand->getTail();

        return $this->buildOrClause($tail, $WhereCollectorExpression, $parameters);
    }

    public function visitAndOperand(AndOperand $andOperand, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $andOperand->getOperand()->accept($this, $parameters);

        $tail = $andOperand->getTail();

        return $this->buildAndClause($tail, $WhereCollectorExpression, $parameters);
    }

    private function buildAndClause(?AndOperand $tail, WhereCollector $WhereCollectorExpression, VisitorParameters $parameters): WhereCollector
    {
        if (!$tail) {
            return $WhereCollectorExpression;
        }

        $WhereCollectorTail = $tail->accept($this, $parameters);

        return new WhereCollector(
            $WhereCollectorExpression->getWhere() . ' AND ' . $WhereCollectorTail->getWhere(),
            array_merge($WhereCollectorExpression->getBindValues(), $WhereCollectorTail->getBindValues()),
        );
    }

    private function buildOrClause(?OrOperand $tail, WhereCollector $WhereCollectorExpression, VisitorParameters $parameters): WhereCollector
    {
        if (!$tail) {
            return $WhereCollectorExpression;
        }

        $WhereCollectorTail = $tail->accept($this, $parameters);

        return new WhereCollector(
            '(' . $WhereCollectorExpression->getWhere() . ' OR ' . $WhereCollectorTail->getWhere() . ')',
            array_merge($WhereCollectorExpression->getBindValues(), $WhereCollectorTail->getBindValues()),
        );
    }
}
