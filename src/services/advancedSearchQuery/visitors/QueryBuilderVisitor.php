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
use Elabftw\Services\AdvancedSearchQuery\Grammar\DateValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Field;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Metadata;
use Elabftw\Services\AdvancedSearchQuery\Grammar\NotExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SimpleValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Exception;
use PDO;
use function random_bytes;

class QueryBuilderVisitor implements Visitor
{
    public function buildWhere(Visitable $parsedQuery, array $parameters): WhereCollector
    {
        return $parsedQuery->accept($this, new VisitorParameters($parameters));
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $value = $simpleValueWrapper->getValue();
        $param = $this->getUniqueID();

            


        return new WhereCollector(
            '(entity.body' . ' LIKE ' . $param . ' OR ' . 'entity.title' . ' LIKE ' . $param . ')',
            //'entity.' . $parameters->getColumn() . ' LIKE ' . $param,
            array(array('param' => $param, 'value' => '%' . $value . '%', 'type' => PDO::PARAM_STR)),
        );
    }

    public function visitDateValueWrapper(DateValueWrapper $dateValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $query = '';
        $bindValues = array();

        $column = 'entity.date';
        $dateType = $dateValueWrapper->getDateType();

        if ($dateType === 'simple') {
            $param = $this->getUniqueID();
            $query = $column . $dateValueWrapper->getOperator() . $param;
            $bindValues[] = array('param' => $param, 'value' => $dateValueWrapper->getValue(), 'type' => PDO::PARAM_INT);
        } elseif ($dateType === 'range') {
            $paramMin = $this->getUniqueID();
            $paramMax = $this->getUniqueID();
            $query = $column . ' BETWEEN ' . $paramMin . ' AND ' . $paramMax;
            $bindValues[] = array('param' => $paramMin, 'value' => $dateValueWrapper->getValue(), 'type' => PDO::PARAM_INT);
            $bindValues[] = array('param' => $paramMax, 'value' => $dateValueWrapper->getDateTo(), 'type' => PDO::PARAM_INT);
        }
        return new WhereCollector($query, $bindValues);
    }

    public function visitField(Field $field, VisitorParameters $parameters): WhereCollector
    {
        // SearchIn:     sets entity, not implemented!
        // Category:     categoryt.id, if entity == items, should set entity!
        // Status:       categoryt.id, if entity == experiment, should set entity!
        // Title:        entity.title
        // Body:         entity.body
        // Author:       CONCAT(users.firstname, ' ', users.lastname)
        // Visibility:   entity.canread
        // Rating:       entity.rating
        // ELabID:       entity.elabid
        // Locked:       entity.locked
        // Attachment:   uploads.has_attachment
        // Timestamped:  entity.timestamped, if entity == experiment

        //!Tag:          complicated

        $fieldName = $field->getField();
        $value = '%' . $field->getValue() . '%';
        $operator = ' LIKE ';
        $param = $this->getUniqueID();
        $bindValuesType = PDO::PARAM_STR;
        $entityType = $parameters->getEntityType();
        switch ($fieldName) {
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
                $column = 'entity.body';
                break;
            case 'category':
                if ($entityType !== 'items') {
                    // This should return a notice to the user
                    // Need a validator visitor
                    return new WhereCollector('1', array());
                }
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
                if ($entityType !== 'experiments') {
                    // This should return a notice to the user
                    // Need a validator visitor
                    return new WhereCollector('1', array());
                }
                $column = 'categoryt.name';
                break;
            case 'timestamped':
                if ($entityType !== 'experiments') {
                    // This should return a notice to the user
                    // Need a validator visitor
                    return new WhereCollector('1', array());
                }
                $column = 'entity.timestamped';
                $operator = ' = ';
                $value = $field->getValue();
                $bindValuesType = PDO::PARAM_INT;
                break;
            case 'title':
                $column = 'entity.title';
                break;
            case 'visibility':
                $column = 'entity.canread';
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
                $final = array_unique(array_intersect_key(array_values($searchArr), $filteredArr));

                if (count($final) > 1) {
                    $queryParts = array();
                    $bindValues = array();
                    foreach ($final as $value) {
                        $param = $this->getUniqueID();
                        $queryParts[] = $column . $operator . $param;
                        $bindValues[] = array(
                            'param' => $param,
                            'value' => $value,
                            'type' => PDO::PARAM_STR,
                        );
                    }
                    return new WhereCollector(implode(' OR ', $queryParts), $bindValues);
                }

                $value = current($final);

                break;
            default:
                // We can never get here because of the parser but to satisfy phpstan
                throw new Exception('We will never get here.');
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

    public function visitMetadata(Metadata $metadata, VisitorParameters $parameters): WhereCollector
    {
        throw new Exception('Not implemented yet!');
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
}
