<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Services\AdvancedSearchQuery\Exceptions\LimitDepthIsExceededException;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Parser;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SyntaxError;
use Elabftw\Services\AdvancedSearchQuery\Visitors\DepthValidatorVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\FieldValidatorVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\QueryBuilderVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AdvancedSearchQuery
{
    protected string $exception = '';

    protected OrExpression $parsedQuery;

    // $depthLimit can be used to limit the depth of the abstract syntax tree. In other words the complexity of the query.
    public function __construct(private string $expertQuery, private VisitorParameters $parameters, private ?int $depthLimit = null)
    {
    }

    public function getWhereClause(): array
    {
        if (!$this->parse()) {
            return array();
        }

        if (!$this->validateDepth()) {
            return array();
        }

        if (!$this->validateFields()) {
            return array();
        }

        $whereClause = (new QueryBuilderVisitor())->buildWhere($this->parsedQuery, $this->parameters);
        return array(
            'where' => ' AND (' . $whereClause->getWhere() . ')',
            'bindValues' => $whereClause->getBindValues(),
        );
    }

    public function getException(): string
    {
        return $this->exception;
    }

    private function parse(): bool
    {
        try {
            $parser = new Parser();
            $this->parsedQuery = $parser->parse($this->expertQuery);
        } catch (SyntaxError $e) {
            $this->exception = 'Line ' . $e->grammarLine . ', Column ' . $e->grammarColumn . ': ' . $e->getMessage();
            return false;
        }
        return true;
    }

    private function validateDepth(): bool
    {
        try {
            (new DepthValidatorVisitor($this->depthLimit))->checkDepthOfTree($this->parsedQuery, $this->parameters);
        } catch (LimitDepthIsExceededException $e) {
            $this->exception = $e->getMessage();
            return false;
        }

        return true;
    }

    private function validateFields(): bool
    {
        $errorArr = (new FieldValidatorVisitor())->check($this->parsedQuery, $this->parameters);

        if (!empty($errorArr)) {
            $this->exception = implode('<br>', $errorArr);
            return false;
        }

        return true;
    }
}
