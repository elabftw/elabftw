<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateFromTitleInterface;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractTemplateEntity extends AbstractEntity implements CreateFromTitleInterface
{
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create($reqBody['title'] ?? _('Untitled')),
            Action::Duplicate => $this->duplicate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }
}
