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
        $message = array();
        if ($parameters->getColumn() !== '') {
            $message[] = 'Field/value pairs are only allowed in advanced tab.';
        }
        return new InvalidFieldCollector($message);
    }

    public function visitField(Field $field, VisitorParameters $parameters): InvalidFieldCollector
    {
        $message = array();
        if ($parameters->getColumn() !== '') {
            return new InvalidFieldCollector(array('Field/value pairs are only allowed in advanced tab.'));
        }

        if ($field->getFieldType() === 'category'
            && $parameters->getEntityType() !== 'items'
        ) {
            $message[] = 'category: is only allowed when searching in database.';
        } elseif (
            $field->getFieldType() === 'status'
            && $parameters->getEntityType() !== 'experiments'
        ) {
            $message[] = 'status: is only allowed when searching in experiments.';
        } elseif (
            $field->getFieldType() === 'timestamped'
            && $parameters->getEntityType() !== 'experiments'
        ) {
            $message[] = 'timestamped: is only allowed when searching in experiments.';
        } elseif ($field->getFieldType() === 'visibility') {
            $visibilityFieldHelper = new VisibilityFieldHelper($field->getValue(), $parameters->getVisArr());
            if (!$visibilityFieldHelper->getArr()) {
                $message[] = 'visibility:<em>' . $field->getValue() . '</em>. Expected values are ' . $visibilityFieldHelper->possibleInput . '.';
            }
        }

        return new InvalidFieldCollector($message);
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
            $tail->getfieldErrors()
        ));
    }
}
