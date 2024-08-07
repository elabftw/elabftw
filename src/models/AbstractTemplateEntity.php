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
 * An entity like Templates or ItemsTypes. Template as opposed to Concrete: Experiments and Items
 */
abstract class AbstractTemplateEntity extends AbstractEntity implements CreateFromTitleInterface
{
    abstract public function create(string $title): int;

    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create($reqBody['title'] ?? _('Untitled')),
            Action::Duplicate => $this->duplicate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }
}
