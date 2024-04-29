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

use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Services\Filter;
use ZipStream\ZipStream;

/**
 * Make a zip with only the modified items on a time period
 */
class MakeBackupZip extends AbstractMakeZip
{
    public function __construct(protected ZipStream $Zip, AbstractConcreteEntity $entity, private string $period, bool $includeChangelog = false)
    {
        parent::__construct(
            entity: $entity,
            includeChangelog: $includeChangelog
        );
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'export.elabftw.zip';
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getStreamZip(): void
    {
        // loop on every user
        $usersArr = $this->Entity->Users->readFromQuery('');
        foreach ($usersArr as $user) {
            $idArr = $this->Entity->getIdFromLastchange((int) $user['userid'], $this->period);
            foreach ($idArr as $id) {
                $this->addToZip((int) $id, $user['fullname']);
            }
        }
        $this->Zip->finish();
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip(int $id, string $fullname): void
    {
        // we're making a backup so ignore permissions access
        $this->Entity->bypassReadPermission = true;
        $this->Entity->setId($id);
        $uploadedFilesArr = $this->Entity->entityData['uploads'];
        $this->folder = Filter::forFilesystem($fullname) . '/' . $this->getBaseFileName();

        if (!empty($uploadedFilesArr)) {
            $this->addAttachedFiles($uploadedFilesArr);
        }
        $this->addPdf();
    }
}
