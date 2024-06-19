<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Models\AbstractEntity;
use Override;

/**
 * Make a full JSON export, including all information from one or several entities
 */
class MakeFullJson extends MakeJson
{
    #[Override]
    protected function getEntityData(AbstractEntity $entity): array
    {
        return $entity->readOneFull();
    }
}
