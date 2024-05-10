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

use Elabftw\Models\Users;
use PDO;
use ZipStream\ZipStream;

/**
 * Make an ELN archive for a user
 */
class MakeUserEln extends AbstractMakeEln
{
    public bool $skipResources = false;

    public function __construct(ZipStream $Zip, protected Users $user)
    {
        parent::__construct($Zip);
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    public function getStreamZip(): void
    {
        $targets = array_map('\Elabftw\Elabftw\EntitySlug::fromString', $this->gatherSlugs());
        $Maker = new MakeEln($this->Zip, $this->user, $targets);
        $Maker->getStreamZip();
    }

    private function gatherSlugs(): array
    {
        $sql = 'SELECT CONCAT("experiments:", experiments.id) AS slug FROM experiments WHERE experiments.userid = :id';
        if ($this->skipResources === false) {
            $sql .= ' UNION All
            SELECT CONCAT("items:", items.id) AS slug FROM items WHERE items.userid = :id';
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->user->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_column($req->fetchAll(), 'slug');
    }
}
