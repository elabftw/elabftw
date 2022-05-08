<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
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
use function ucfirst;

class QueryBuilderVisitor implements Visitor
{
    public function buildWhere(Visitable $parsedQuery, VisitorParameters $parameters): WhereCollector
    {
        return $parsedQuery->accept($this, $parameters);
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $param = $this->getUniqueID();

        return new WhereCollector(
            '(entity.body LIKE ' . $param . ' OR entity.title LIKE ' . $param . ')',
            array(array(
                'param' => $param,
                'value' => '%' . $simpleValueWrapper->getValue() . '%',
                'type' => PDO::PARAM_STR,
            )),
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
            $bindValues[] = array(
                'param' => $param,
                'value' => $dateField->getValue(),
                'type' => PDO::PARAM_INT,
            );
        } elseif ($dateType === 'range') {
            $paramMin = $this->getUniqueID();
            $paramMax = $this->getUniqueID();
            $query = $column . ' BETWEEN ' . $paramMin . ' AND ' . $paramMax;
            $bindValues[] = array(
                'param' => $paramMin,
                'value' => $dateField->getValue(),
                'type' => PDO::PARAM_INT,
            );
            $bindValues[] = array(
                'param' => $paramMax,
                'value' => $dateField->getDateTo(),
                'type' => PDO::PARAM_INT,
            );
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

        // Call class methods dynamically to avoid many if statements.
        // This works here because the parser defines the list of fields.
        $method = 'visitField' . ucfirst($field->getFieldType());
        return $this->$method($field->getValue(), $field->getAffix(), $parameters);
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

    private function getWhereCollector(string $sql, string $searchTerm, int $PdoParamConst): WhereCollector
    {
        $param = $this->getUniqueID();
        return new WhereCollector(
            $sql . $param,
            array(array(
                'param' => $param,
                'value' => $searchTerm,
                'type' => $PdoParamConst,
            )),
        );
    }

    private function visitFieldAttachment(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        // Are we checking if there is any attachment at all
        if ($searchTerm === '0' || $searchTerm === '1') {
            return $this->getWhereCollector(
                'IFNULL(uploads.has_attachment, 0) = ',
                $searchTerm,
                PDO::PARAM_INT,
            );
        }

        // Or are we searching in comments or real_names
        $param = $this->getUniqueID();

        return new WhereCollector(
            '(uploads.comments LIKE ' . $param . ' OR uploads.real_names LIKE ' . $param . ')',
            array(array(
                'param' => $param,
                'value' => $affix . $searchTerm . $affix,
                'type' => PDO::PARAM_STR,
                'searchAttachments' => true,
            )),
        );
    }

    private function visitFieldAuthor(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            "CONCAT(users.firstname, ' ', users.lastname) LIKE ",
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldBody(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.body LIKE ',
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldCategory(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'categoryt.name LIKE ',
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldElabid(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.elabid LIKE ',
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldGroup(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        $teamGroups = $parameters->getTeamGroups();
        $users = array();
        foreach ($teamGroups as $teamGroup) {
            if ($searchTerm === $teamGroup['name']) {
                array_push($users, ...array_column($teamGroup['users'], 'userid'));
            }
        }
        $queryParts = array('0');
        $bindValues = array();
        foreach (array_unique($users) as $user) {
            $param = $this->getUniqueID();
            $queryParts[] = 'users.userid = ' . $param;
            $bindValues[] = array(
                'param' => $param,
                'value' => $user,
                'type' => PDO::PARAM_INT,
            );
        }

        return new WhereCollector('(' . implode(' OR ', $queryParts) . ')', $bindValues);
    }

    private function visitFieldLocked(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.locked = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
    }

    private function visitFieldRating(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.rating = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
    }

    private function visitFieldStatus(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'categoryt.name LIKE ',
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldTimestamped(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.timestamped = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
    }

    private function visitFieldTitle(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.title LIKE ',
            $affix . $searchTerm . $affix,
            PDO::PARAM_STR,
        );
    }

    private function visitFieldVisibility(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        $filteredSearchArr = (new VisibilityFieldHelper($searchTerm, $parameters->getVisArr(), $affix))->getArr();

        $queryParts = array();
        $bindValues = array();
        foreach ($filteredSearchArr as $value) {
            $param = $this->getUniqueID();
            $queryParts[] = 'entity.canread LIKE ' . $param;
            $bindValues[] = array(
                'param' => $param,
                'value' => $value,
                'type' => PDO::PARAM_STR,
            );
        }

        return new WhereCollector('(' . implode(' OR ', $queryParts) . ')', $bindValues);
    }
}
