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

use Elabftw\Enums\State;
use Elabftw\Models\Users\UltraAdmin;
use PDO;
use ZipStream\ZipStream;
use Override;

/**
 * Make an ELN archive for a full team. Only accessible from command line.
 */
final class MakeTeamEln extends AbstractMakeEln
{
    public function __construct(ZipStream $Zip, protected int $teamId, protected array $users = array(), protected array $resourcesCategories = array())
    {
        parent::__construct($Zip);
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    #[Override]
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
        $usersAnd = '';
        if ($this->users) {
            $usersAnd .= sprintf(' AND userid IN (%s)', implode(',', $this->users));
        }
        $resourcesCategoriesAnd = '';
        if ($this->resourcesCategories) {
            $resourcesCategoriesAnd .= sprintf(' AND category IN (%s)', implode(',', $this->resourcesCategories));
        }
        // we don't grab the deleted ones
        $sql = sprintf(
            'SELECT CONCAT("experiments:", experiments.id) AS slug
                FROM experiments
                WHERE experiments.team = :teamid
                    AND state IN (:state_normal, :state_archived)
                    %1$s
                UNION All
                SELECT CONCAT("items:", items.id) AS slug
                FROM items
                WHERE items.team = :teamid
                    AND state IN (:state_normal, :state_archived)
                    %2$s
                UNION All
                SELECT CONCAT("experiments_templates:", experiments_templates.id) AS slug
                FROM experiments_templates
                WHERE experiments_templates.team = :teamid
                    AND state IN (:state_normal, :state_archived)
                    %1$s
                UNION All
                SELECT CONCAT("items_types:", items_types.id) AS slug
                FROM items_types
                WHERE items_types.team = :teamid
                    AND state IN (:state_normal, :state_archived)',
            $usersAnd,
            $resourcesCategoriesAnd,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':teamid', $this->teamId, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_column($req->fetchAll(), 'slug');
    }
}
