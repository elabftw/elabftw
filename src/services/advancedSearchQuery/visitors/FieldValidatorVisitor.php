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

use Elabftw\Services\AdvancedSearchQuery\Collectors\InvalidFieldCollector;
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
use function sprintf;

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

    public function visitField(Field $field, VisitorParameters $parameters): InvalidFieldCollector
    {
        // Call class methods dynamically to avoid many if statements.
        // This works here because the parser defines the list of fields.
        $method = 'visitField' . ucfirst($field->getFieldType());
        return $this->$method($field->getValue(), $field->getAffix(), $parameters);
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

    private function visitFieldAttachment(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldAuthor(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldBody(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldCategory(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        if ($parameters->getEntityType() !== 'items') {
            return new InvalidFieldCollector(array('category: is only allowed when searching in database.'));
        }
        return new InvalidFieldCollector();
    }

    private function visitFieldElabid(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
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

    private function visitFieldLocked(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldRating(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldStatus(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        if ($parameters->getEntityType() !== 'experiments') {
            return new InvalidFieldCollector(array('status: is only allowed when searching in experiments.'));
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

    private function visitFieldTitle(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        return new InvalidFieldCollector();
    }

    private function visitFieldVisibility(string $searchTerm, string $affix, VisitorParameters $parameters): InvalidFieldCollector
    {
        $visibilityFieldHelper = new VisibilityFieldHelper($searchTerm, $parameters->getVisArr(), $affix);
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
