<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Models\UltraAdmin;
use PDO;
use ZipStream\ZipStream;

/**
 * Make an ELN archive for a full team. Only accessible from command line.
 */
class MakeTeamEln extends AbstractMakeEln
{
    public function __construct(ZipStream $Zip, protected int $teamId)
    {
        parent::__construct($Zip);
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    public function getStreamZip(): void
    {
        $targets = array_map('\Elabftw\Elabftw\EntitySlug::fromString', $this->gatherSlugs());
        $entityArr = array();
        $requester = new UltraAdmin(team: $this->teamId);
        foreach ($targets as $slug) {
            $entityArr[] = $slug->type->toInstance($requester, $slug->id, bypassReadPermission: true, bypassWritePermission: true);
        }
        $Maker = new MakeEln($this->Zip, $requester, $entityArr);
        $Maker->bypassReadPermission = true;
        $Maker->getStreamZip();
    }

    private function gatherSlugs(): array
    {
        // we don't grab the deleted ones
        $sql = 'SELECT CONCAT("experiments:", experiments.id) AS slug FROM experiments WHERE experiments.team = :teamid AND state IN (1, 2)
            UNION All
            SELECT CONCAT("items:", items.id) AS slug FROM items WHERE items.team = :teamid AND state IN (1, 2)
            UNION All
            SELECT CONCAT("experiments_templates:", experiments_templates.id) AS slug FROM experiments_templates WHERE experiments_templates.team = :teamid AND state IN (1, 2)
            UNION All
            SELECT CONCAT("items_types:", items_types.id) AS slug FROM items_types WHERE items_types.team = :teamid AND state IN (1, 2)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':teamid', $this->teamId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_column($req->fetchAll(), 'slug');
    }
}
