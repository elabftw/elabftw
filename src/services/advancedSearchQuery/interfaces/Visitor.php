<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

interface Visitor extends VisitOrExpression, VisitOrOperand, VisitAndExpression, VisitAndOperand, VisitNotExpression, VisitSimpleValueWrapper, VisitField, VisitDateField
{
}
