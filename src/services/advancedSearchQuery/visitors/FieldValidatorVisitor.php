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

use Elabftw\Services\AdvancedSearchQuery\Collectors\InvalidFieldCollector;
use Elabftw\Services\AdvancedSearchQuery\Enums\Fields;
use Elabftw\Services\AdvancedSearchQuery\Enums\TimestampFields;
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

use function sprintf;
use function ucfirst;

/** @psalm-suppress UnusedParam */
class FieldValidatorVisitor implements Visitor
{
    public function check(Visitable $parsedQuery, VisitorParameters $parameters): array
    {
        return $parsedQuery->accept($this, $parameters)->getfieldErrors();
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $simpleValueWrapper, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    public function visitMetadataField(MetadataField $metadataField, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    public function visitDateField(DateField $dateField, VisitorParameters $parameters): InvalidFieldCollector
    {
        if ($dateField->getDateType() === 'range' && $dateField->getValue() > $dateField->getDateTo()) {
            $message = sprintf(
                'date:%s..%s. Second date needs to be equal or greater than first date.',
                $dateField->getValue(),
                $dateField->getDateTo(),
            );
            return new InvalidFieldCollector(array($message));
        }

        return new InvalidFieldCollector();
    }

    public function visitTimestampField(TimestampField $timestampField, VisitorParameters $parameters): InvalidFieldCollector
    {
        $messages = array();

        if (!in_array($parameters->getEntityType(), array('experiments', 'items'), true)
            && $timestampField->getFieldType() === TimestampFields::TimestampedAt
        ) {
            $messages[] = sprintf(
                '%s: is only allowed when searching in experiments.',
                TimestampFields::TimestampedAt->value,
            );
        }

        // MySQL range for TIMESTAMP values is '1970-01-01 00:00:01.000000' to '2038-01-19 03:14:07.999999'
        // We use 1970-01-02 and 2038-01-18 because time 00:00:00 and/or 23:59:59 will be added
        $DateMin = 19700102;
        $DateMax = 20380118;

        if ((intval($timestampField->getValue(), 10) < $DateMin) || (intval($timestampField->getValue(), 10) > $DateMax)
            || ($timestampField->getDateType() === 'range' && ((intval($timestampField->getDateTo(), 10) < $DateMin) || (intval($timestampField->getDateTo(), 10) > $DateMax)))
        ) {
            $messages[] = sprintf(
                '%s: Date needs to be between 1970-01-02 and 2038-01-18.',
                $timestampField->getFieldType()->value,
            );
        }

        if ($timestampField->getDateType() === 'range' && $timestampField->getValue() > $timestampField->getDateTo()) {
            $messages[] = sprintf(
                '%s:%s..%s. Second date needs to be equal or greater than first date.',
                $timestampField->getFieldType()->value,
                $timestampField->getValue(),
                $timestampField->getDateTo(),
            );
        }

        return new InvalidFieldCollector($messages);
    }

    public function visitField(Field $field, VisitorParameters $parameters): InvalidFieldCollector
    {
        // only add the class methods that are actually used
        if ($field->getFieldType() === Fields::Visibility
            || $field->getFieldType() === Fields::Timestamped
            || $field->getFieldType() === Fields::Group
        ) {
            // Call class methods dynamically to avoid many if statements.
            // This works because the parser and the Fields enum define the list of fields.
            $method = 'visitField' . ucfirst($field->getFieldType()->value);
            return $this->$method($field->getValue(), $field->getAffix(), $parameters);
        }

        return new InvalidFieldCollector();
    }

    public function visitNotExpression(NotExpression $notExpression, VisitorParameters $parameters): InvalidFieldCollector
    {
        $invalidFieldCollectorExpression = $notExpression->getExpression()->accept($this, $parameters);

        return new InvalidFieldCollector($invalidFieldCollectorExpression->getfieldErrors());
    }

    public function visitAndExpression(AndExpression $andExpression, VisitorParameters $parameters): InvalidFieldCollector
    {
        return $this->visitExpression($andExpression, $parameters);
    }

    public function visitOrExpression(OrExpression $orExpression, VisitorParameters $parameters): InvalidFieldCollector
    {
        return $this->visitExpression($orExpression, $parameters);
    }

    public function visitOrOperand(OrOperand $orOperand, VisitorParameters $parameters): InvalidFieldCollector
    {
        return $this->visitOperand($orOperand, $parameters);
    }

    public function visitAndOperand(AndOperand $andOperand, VisitorParameters $parameters): InvalidFieldCollector
    {
        return $this->visitOperand($andOperand, $parameters);
    }

    private function visitTail(null | OrExpression | AndExpression | OrOperand | AndOperand $tail, VisitorParameters $parameters): InvalidFieldCollector
    {
        if ($tail) {
            return $tail->accept($this, $parameters);
        }
        return new InvalidFieldCollector();
    }

    private function visitOperand(OrOperand | AndOperand $operand, VisitorParameters $parameters): InvalidFieldCollector
    {
        $head = $operand->getOperand()->accept($this, $parameters);
        $tail = $this->visitTail($operand->getTail(), $parameters);

        return $this->mergeHeadTail($head, $tail);
    }

    private function visitExpression(OrExpression | AndExpression $expression, VisitorParameters $parameters): InvalidFieldCollector
    {
        $head = $expression->getExpression()->accept($this, $parameters);
        $tail = $this->visitTail($expression->getTail(), $parameters);

        return $this->mergeHeadTail($head, $tail);
    }

    private function mergeHeadTail(InvalidFieldCollector $head, InvalidFieldCollector $tail): InvalidFieldCollector
    {
        return new InvalidFieldCollector(array_merge(
            $head->getfieldErrors(),
            $tail->getfieldErrors(),
        ));
    }

    private function visitFieldGroup(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        $teamGroups = $parameters->getTeamGroups();
        $groupNames = array_column($teamGroups, 'name');
        if (!in_array($searchTerm, $groupNames, true)) {
            $message = sprintf(
                'group:%s. Valid values are %s.',
                $searchTerm,
                implode(', ', $groupNames),
            );
            return new InvalidFieldCollector(array($message));
        }
        return new InvalidFieldCollector();
    }

    private function visitFieldTimestamped(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        if ($parameters->getEntityType() !== 'experiments') {
            return new InvalidFieldCollector(array('timestamped: is only allowed when searching in experiments.'));
        }
        return new InvalidFieldCollector();
    }

    private function visitFieldVisibility(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        $visibilityFieldHelper = new VisibilityFieldHelper($searchTerm);
        if (!$visibilityFieldHelper->getArr()) {
            $message = sprintf(
                'visibility:%s. Valid values are %s.',
                $searchTerm,
                $visibilityFieldHelper->possibleInput,
            );
            return new InvalidFieldCollector(array($message));
        }
        return new InvalidFieldCollector();
    }
}
