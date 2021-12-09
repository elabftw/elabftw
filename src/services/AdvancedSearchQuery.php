<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Services\AdvancedSearchQuery\Exceptions\LimitDepthIsExceededException;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Parser;
use Elabftw\Services\AdvancedSearchQuery\Grammar\ParserWithoutFields;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SyntaxError;
use Elabftw\Services\AdvancedSearchQuery\Visitors\DepthValidatorVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\FieldValidatorVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\QueryBuilderVisitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AdvancedSearchQuery
{
    protected string $exception = '';

    // $depthLimit can be used to limit the depth of the abstract syntax tree. In other words the complexity of the query.
    public function __construct(private string $expertQuery, private VisitorParameters $parameters, private ?int $depthLimit = null)
    {
    }

    public function getWhereClause(): array
    {
        $whereClause = array();
        try {
            $parser = $this->parameters->getColumn() ? new ParserWithoutFields() : new Parser();
            $parsedQuery = $parser->parse($this->expertQuery);
        } catch (SyntaxError $e) {
            $line = $this->parameters->getColumn() ? '' : 'Line ' . $e->grammarLine . ', ';
            $this->exception = $line . 'Column ' . $e->grammarColumn . ': ' . $e->getMessage();
            return $whereClause;
        }

        try {
            (new DepthValidatorVisitor($this->depthLimit))->checkDepthOfTree($parsedQuery, $this->parameters);
        } catch (LimitDepthIsExceededException $e) {
            $this->exception = 'Query is too complex.';
            return $whereClause;
        }

        $errorArr = (new FieldValidatorVisitor())->check($parsedQuery, $this->parameters);
        
        if ($errorArr) {
            $this->exception = implode('<br>', $errorArr);
            return $whereClause;
        }

        $whereClause = (new QueryBuilderVisitor())->buildWhere($parsedQuery, $this->parameters);
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
