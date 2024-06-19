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
        // we use an empty user object here because we must not care about permissions
        $Maker = new MakeEln($this->Zip, new UltraAdmin(team: $this->teamId), $targets);
        $Maker->bypassReadPermission = true;
        $Maker->getStreamZip();
    }

    private function gatherSlugs(): array
    {
        $sql = 'SELECT CONCAT("experiments:", experiments.id) AS slug FROM experiments WHERE experiments.team = :teamid
            UNION All
            SELECT CONCAT("items:", items.id) AS slug FROM items WHERE items.team = :teamid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':teamid', $this->teamId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_column($req->fetchAll(), 'slug');
    }
}
