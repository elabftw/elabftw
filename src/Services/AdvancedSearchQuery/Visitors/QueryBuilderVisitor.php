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

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\AndOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\DateField;
use Elabftw\Services\AdvancedSearchQuery\Grammar\Field;
use Elabftw\Services\AdvancedSearchQuery\Grammar\MetadataField;
use Elabftw\Services\AdvancedSearchQuery\Grammar\NotExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrExpression;
use Elabftw\Services\AdvancedSearchQuery\Grammar\OrOperand;
use Elabftw\Services\AdvancedSearchQuery\Grammar\SimpleValueWrapper;
use Elabftw\Services\AdvancedSearchQuery\Grammar\TimestampField;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use PDO;
use Override;

use function array_merge;
use function bin2hex;
use function random_bytes;
use function ucfirst;

/** @psalm-suppress UnusedParam */
final class QueryBuilderVisitor implements Visitor
{
    public function buildWhere(Visitable $parsedQuery, VisitorParameters $parameters): WhereCollector
    {
        return $parsedQuery->accept($this, $parameters);
    }

    #[Override]
    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): WhereCollector
    {
        $param = $this->getUniqueID();
        $paramBody = $this->getUniqueID();
        $query = sprintf(
            '(entity.title LIKE %1$s
                OR entity.date LIKE %1$s
                OR entity.elabid LIKE %1$s
                OR compounds.cas_number LIKE %1$s
                OR compounds.ec_number LIKE %1$s
                OR compounds.name LIKE %1$s
                OR compounds.iupac_name LIKE %1$s
                OR compounds.inchi_key LIKE %1$s
                OR compounds.molecular_formula LIKE %1$s
                OR entity.body LIKE %2$s)',
            $param,
            $paramBody,
        );

        $bindValues = array();
        $bindValues[] = array(
            'param' => $param,
            'value' => '%' . $simpleValueWrapper->getValue() . '%',
        );
        // body is stored as html after htmlPurifier worked on it
        // so '<', '>', '&' need to be converted to their htmlentities &lt;, &gt;, &amp;
        $bindValues[] = array(
            'param' => $paramBody,
            'value' => '%' . htmlspecialchars($simpleValueWrapper->getValue(), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML401) . '%',
        );

        return new WhereCollector($query, $bindValues);
    }

    #[Override]
    public function visitMetadataField(MetadataField $metadataField, VisitorParameters $parameters): WhereCollector
    {
        $pathParam = $this->getUniqueID();
        $valueParam = $this->getUniqueID();
        $column = 'entity.metadata';
        $query = sprintf(
            'JSON_UNQUOTE(JSON_EXTRACT(LOWER(%s), LOWER(%s))) LIKE LOWER(%s)',
            $column,
            $pathParam,
            $valueParam,
        );

        $bindValues = array();
        // value path
        $bindValues[] = array(
            'param' => $pathParam,
            'value' => sprintf(
                '$.%s%s.%s',
                MetadataEnum::ExtraFields->value,
                // JSON path '$.extra_fields**.value' can be used to search all keys
                // Note: the extraFieldKey gets double quoted by json_encode() so spaces are not an issue
                $metadataField->getKey() === '**'
                    ? '**'
                    : '.' . json_encode($metadataField->getKey(), JSON_HEX_APOS | JSON_THROW_ON_ERROR),
                MetadataEnum::Value->value,
            ),
            'additional_columns' => $column,
        );
        // value
        $bindValues[] = array(
            'param' => $valueParam,
            'value' => $metadataField->getAffix() . $metadataField->getValue() . $metadataField->getAffix(),
            'additional_columns' => $column,
        );

        return new WhereCollector($query, $bindValues);
    }

    #[Override]
    public function visitDateField(DateField $dateField, VisitorParameters $parameters): WhereCollector
    {
        $query = '';
        $bindValues = array();

        $column = 'entity.date';
        $dateType = $dateField->getDateType();

        if ($dateType === 'simple') {
            $param = $this->getUniqueID();
            $query = $column . ' ' . $dateField->getOperator() . ' ' . $param;
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

    #[Override]
    public function visitTimestampField(TimestampField $timestampField, VisitorParameters $parameters): WhereCollector
    {
        $query = '';
        $bindValues = array();

        $timeMin = '000000';
        $timeMax = '235959';

        $column = 'entity.' . $timestampField->getFieldType()->value;
        $dateType = $timestampField->getDateType();

        // convert date (YYYYMMDD) to timestamp (YYYYMMDDhhmmss) depending on operator
        // >=   date . '000000'
        // <    date . '000000'
        // >    date . '235959'
        // <=   date . '235959'
        // !=   < date . '000000' OR > date . '235959'
        // =    BETWEEN date . '000000' AND date . '235959'

        if ($dateType === 'simple') {
            if (in_array($timestampField->getOperator(), array('=', '!='), true)) {
                $paramMin = $this->getUniqueID();
                $paramMax = $this->getUniqueID();
                if ($timestampField->getOperator() === '=') {
                    $query = $column . ' BETWEEN ' . $paramMin . ' AND ' . $paramMax;
                }
                if ($timestampField->getOperator() === '!=') {
                    $query = $column . ' < ' . $paramMin . ' OR ' . $column . ' > ' . $paramMax;
                }
                $bindValues[] = array(
                    'param' => $paramMin,
                    'value' => $timestampField->getValue() . $timeMin,
                    'type' => PDO::PARAM_INT,
                    'additional_columns' => $column,
                );
                $bindValues[] = array(
                    'param' => $paramMax,
                    'value' => $timestampField->getValue() . $timeMax,
                    'type' => PDO::PARAM_INT,
                    'additional_columns' => $column,
                );
                return new WhereCollector($query, $bindValues);
            }

            // operator is >= or <
            $time = $timeMin;
            if (in_array($timestampField->getOperator(), array('<=', '>'), true)) {
                $time = $timeMax;
            }
            $param = $this->getUniqueID();
            $query = $column . ' ' . $timestampField->getOperator() . ' ' . $param;
            $bindValues[] = array(
                'param' => $param,
                'value' => $timestampField->getValue() . $time,
                'type' => PDO::PARAM_INT,
                'additional_columns' => $column,
            );
            return new WhereCollector($query, $bindValues);
        }
        if ($dateType === 'range') {
            $paramMin = $this->getUniqueID();
            $paramMax = $this->getUniqueID();
            $query = $column . ' BETWEEN ' . $paramMin . ' AND ' . $paramMax;
            $bindValues[] = array(
                'param' => $paramMin,
                'value' => $timestampField->getValue() . $timeMin,
                'type' => PDO::PARAM_INT,
                'additional_columns' => $column,
            );
            $bindValues[] = array(
                'param' => $paramMax,
                'value' => $timestampField->getDateTo() . $timeMax,
                'type' => PDO::PARAM_INT,
                'additional_columns' => $column,
            );
        }
        return new WhereCollector($query, $bindValues);
    }

    #[Override]
    public function visitField(Field $field, VisitorParameters $parameters): WhereCollector
    {
        // Author:       CONCAT(users.firstname, ' ', users.lastname)
        // Body:         entity.body
        // Category:     categoryt.title
        // Custom_id:    entity.custom_id
        // ELabID:       entity.elabid
        // Id:           entity.id
        // Locked:       entity.locked
        // Owner:        CONCAT(users.firstname, ' ', users.lastname)
        // Rating:       entity.rating
        // State:        entity.state
        // Status:       statust.title
        // Timestamped:  entity.timestamped, if entity == experiment
        // Title:        entity.title
        // Visibility:   entity.canread

        // SearchIn:     sets entity, not implemented!
        // Tag and Metadata not implemented!

        // Call class methods dynamically to avoid many if statements.
        // This works because the parser and the Fields enum define the list of fields.
        $method = 'visitField' . ucfirst($field->getFieldType()->value);
        return $this->$method($field->getValue(), $field->getAffix(), $parameters);
    }

    #[Override]
    public function visitNotExpression(NotExpression $notExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $notExpression->getExpression()->accept($this, $parameters);

        return new WhereCollector(
            'NOT (' . $WhereCollectorExpression->getWhere() . ')',
            $WhereCollectorExpression->getBindValues(),
        );
    }

    #[Override]
    public function visitAndExpression(AndExpression $andExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $andExpression->getExpression()->accept($this, $parameters);

        $tail = $andExpression->getTail();

        return $this->buildAndClause($tail, $WhereCollectorExpression, $parameters);
    }

    #[Override]
    public function visitOrExpression(OrExpression $orExpression, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $orExpression->getExpression()->accept($this, $parameters);

        $tail = $orExpression->getTail();

        return $this->buildOrClause($tail, $WhereCollectorExpression, $parameters);
    }

    #[Override]
    public function visitOrOperand(OrOperand $orOperand, VisitorParameters $parameters): WhereCollector
    {
        $WhereCollectorExpression = $orOperand->getOperand()->accept($this, $parameters);

        $tail = $orOperand->getTail();

        return $this->buildOrClause($tail, $WhereCollectorExpression, $parameters);
    }

    #[Override]
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

    private function getWhereCollector(string $sql, string $searchTerm, int $PdoParamConst = PDO::PARAM_STR): WhereCollector
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

    private function visitFieldAuthor(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            "CONCAT(users.firstname, ' ', users.lastname) LIKE ",
            $affix . $searchTerm . $affix,
        );
    }

    private function visitFieldOwner(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            "CONCAT(users.firstname, ' ', users.lastname) LIKE ",
            $affix . $searchTerm . $affix,
        );
    }

    private function visitFieldBody(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.body LIKE ',
            $affix . $searchTerm . $affix,
        );
    }

    private function visitFieldCategory(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'categoryt.title LIKE ',
            $affix . $searchTerm . $affix,
        );
    }

    private function visitFieldCustom_id(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.custom_id = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
    }

    private function visitFieldElabid(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.elabid LIKE ',
            $affix . $searchTerm . $affix,
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

    private function visitFieldId(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.id = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
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

    private function visitFieldState(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'entity.state = ',
            $searchTerm,
            PDO::PARAM_INT,
        );
    }

    private function visitFieldStatus(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        return $this->getWhereCollector(
            'statust.title LIKE ',
            $affix . $searchTerm . $affix,
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
        );
    }

    private function visitFieldVisibility(string $searchTerm, string $affix, VisitorParameters $parameters): WhereCollector
    {
        $filteredSearchArr = (new VisibilityFieldHelper($searchTerm))->getArr();

        $queryParts = array();
        $bindValues = array();
        foreach ($filteredSearchArr as $value) {
            $param = $this->getUniqueID();
            $queryParts[] = "JSON_EXTRACT(entity.canread, '$.base') = " . $param;
            $bindValues[] = array(
                'param' => $param,
                'value' => $value,
                'type' => PDO::PARAM_INT,
            );
        }

        return new WhereCollector('(' . implode(' OR ', $queryParts) . ')', $bindValues);
    }
}
