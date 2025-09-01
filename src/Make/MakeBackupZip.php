<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Filter;
use Override;

/**
 * Make a zip with a folder for every owner of the entity
 */
final class MakeBackupZip extends MakeStreamZip
{
    #[Override]
    protected function getFolder(AbstractEntity $entity): string
    {
        $owner = new Users($entity->entityData['userid']);
        return Filter::forFilesystem($owner->userData['fullname']) . '/' . $entity->toFsTitle() . '/';
    }
}
