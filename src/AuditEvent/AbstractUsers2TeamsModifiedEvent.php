<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

abstract class AbstractUsers2TeamsModifiedEvent extends AbstractAuditEvent
{
    public function getCategory(): int
    {
        return AuditCategory::Users2TeamsModified->value;
    }
}
