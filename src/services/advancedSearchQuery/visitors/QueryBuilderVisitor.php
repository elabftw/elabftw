<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2021 Nicolas CARPi
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
use Elabftw\Services\AdvancedSearchQuery\Grammar\DateField;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Field;
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
    public function buildWhere(Visitable $parsedQuery, VisitorParameters $parameters): WhereCollector
    {
        return $parsedQuery->accept($this, $parameters);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $param = $this->getUniqueID();
        $query = '(entity.body' . ' LIKE ' . $param . ' OR ' . 'entity.title' . ' LIKE ' . $param . ')';
        if ($parameters->getColumn() !== '') {
            $query = 'entity.' . $parameters->getColumn() . ' LIKE ' . $param;
        }

        return new WhereCollector(
            $query,
            array(array('param' => $param, 'value' => '%' . $simpleValueWrapper->getValue() . '%', 'type' => PDO::PARAM_STR)),
        );
    }

    public function visitDateField(DateField $dateField, VisitorParameters $parameters): WhereCollector
    {
        $query = '';
        $bindValues = array();

        $column = 'entity.date';
        $dateType = $dateField->getDateType();

        if ($dateType === 'simple') {
            $param = $this->getUniqueID();
            $query = $column . $dateField->getOperator() . $param;
            $bindValues[] = array('param' => $param, 'value' => $dateField->getValue(), 'type' => PDO::PARAM_INT);
        } elseif ($dateType === 'range') {
            $paramMin = $this->getUniqueID();
            $paramMax = $this->getUniqueID();
            $query = $column . ' BETWEEN ' . $paramMin . ' AND ' . $paramMax;
            $bindValues[] = array('param' => $paramMin, 'value' => $dateField->getValue(), 'type' => PDO::PARAM_INT);
            $bindValues[] = array('param' => $paramMax, 'value' => $dateField->getDateTo(), 'type' => PDO::PARAM_INT);
        }
        return new WhereCollector($query, $bindValues);
    }

    public function visitField(Field $field, VisitorParameters $parameters): WhereCollector
    {
        // Attachment:   uploads.has_attachment
        // Author:       CONCAT(users.firstname, ' ', users.lastname)
        // Body:         entity.body
        // Category:     categoryt.id, if entity == items, should set entity!
        // ELabID:       entity.elabid
        // Locked:       entity.locked
        // Rating:       entity.rating
        // Status:       categoryt.id, if entity == experiment, should set entity!
        // Timestamped:  entity.timestamped, if entity == experiment
        // Title:        entity.title
        // Visibility:   entity.canread

        // SearchIn:     sets entity, not implemented!
        // Tag and Metadata not implemented!

        $value = '%' . $field->getValue() . '%';
        $operator = ' LIKE ';
        $param = $this->getUniqueID();
        $bindValuesType = PDO::PARAM_STR;
        $column = 'entity.body';
        switch ($field->getField()) {
            case 'attachment':
                $column = 'IFNULL(uploads.has_attachment, 0)';
                $operator = ' = ';
                $value = $field->getValue();
                $bindValuesType = PDO::PARAM_INT;
                break;
            case 'author':
                $column = "CONCAT(users.firstname, ' ', users.lastname)";
                break;
            case 'body':
                // Nothing to do here
                break;
            case 'category':
                $column = 'categoryt.name';
                break;
            case 'elabid':
                $column = 'entity.elabid';
                break;
            case 'locked':
                $column = 'entity.locked';
                $operator = ' = ';
                $value = $field->getValue();
                $bindValuesType = PDO::PARAM_INT;
                break;
            case 'rating':
                $column = 'entity.rating';
                $operator = ' = ';
                $value = $field->getValue();
                $bindValuesType = PDO::PARAM_INT;
                break;
            case 'status':
                $column = 'categoryt.name';
                break;
            case 'timestamped':
                $column = 'entity.timestamped';
                $operator = ' = ';
                $value = $field->getValue();
                $bindValuesType = PDO::PARAM_INT;
                break;
            case 'title':
                $column = 'entity.title';
                break;
            case 'visibility':
                // Need to convert team groups names to the corresponding ID's.
                // TeamGroups::getVisibilityList() result gets injected; available via getVisArr()
                $visArr = array_flip(array_map('strtolower', $parameters->getVisArr()));
                $onlyStringsArr = array_filter($visArr, 'is_string');
                $searchArr = $visArr + array_combine($onlyStringsArr, $onlyStringsArr);
                // Emulate SQL LIKE search functionality so the user can use the same placeholders
                $pattern = '/' . str_replace(array('%', '_'), array('.*', '.'), $field->getValue()) . '/i';
                // Filter visibility entries based on user input
                $filteredArr = preg_grep($pattern, array_keys($searchArr)) ?: array();
                // Get a unique list of visibility entries that can be used in the SQL where clause
                $filteredSearchArr = array_unique(array_intersect_key(array_values($searchArr), $filteredArr));

                return $this->getVisibilityWhereCollector($filteredSearchArr);
        }

        return new WhereCollector(
            $column . $operator . $param,
            array(array(
                'param' => $param,
                'value' => $value,
                'type' => $bindValuesType,
            )),
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

    /*
     * Generate a unique named parameter identifier used with PDO::prepare and bindValue in src/models/AbstractEntity.php.
     * Cannot use question mark (?) parameter because of the other named parameters.
     */
    private function getUniqueID(): string
    {
        return ':' . bin2hex(random_bytes(5));
    }

    private function getVisibilityWhereCollector(array $final): WhereCollector
    {
        // Todo: what to return if final is empty
        // => need Field Validator to catch this case!
        $bindValues = array();
        $queryParts = array();
        foreach ($final as $value) {
            $param = $this->getUniqueID();
            $queryParts[] = 'entity.canread LIKE ' . $param;
            $bindValues[] = array(
                'param' => $param,
                'value' => $value,
                'type' => PDO::PARAM_STR,
            );
        }

        return new WhereCollector(implode(' OR ', $queryParts), $bindValues);
    }
}
