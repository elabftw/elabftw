<?php declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Services\AdvancedSearchQuery\Exceptions\LimitDepthIsExceededException;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Parser;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SyntaxError;
use Elabftw\Services\AdvancedSearchQuery\Visitors\DepthValidatorVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\QueryBuilderVisitor;

class AdvancedSearchQuery
{
    protected string $exception = '';

    public function __construct(private string $expertQuery, private string $column, private ?int $depthLimit = null)
    {
    }

    public function getWhereClause(): array
    {
        $whereClause = array();
        try {
            $parsedQuery = (new Parser())->parse($this->expertQuery);
        } catch (SyntaxError $e) {
            $this->exception = 'Column ' . $e->grammarColumn . ': ' . $e->getMessage();
            return $whereClause;
        }

        try {
            (new DepthValidatorVisitor($this->depthLimit))->checkDepthOfTree($parsedQuery, $this->column);
        } catch (LimitDepthIsExceededException $e) {
            $this->exception = 'Query is to complex.';
            return $whereClause;
        }

        $whereClause = (new QueryBuilderVisitor())->buildWhere($parsedQuery, $this->column);
        return array(
            'where' => ' AND (' . $whereClause->getWhere() . ')',
            'bindValues' => $whereClause->getBindValues(),
        );
    }

    public function getException(): string
    {
        return $this->exception;
    }
}
