<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\UserUploadsQueryParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use PDO;
use Symfony\Component\HttpFoundation\Request;

class UserUploads implements RestInterface
{
    protected Db $Db;

    public function __construct(private Users $owner, private ?int $id = null)
    {
        $this->Db = Db::getConnection();
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('No POST action for this endpoint');
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action for this endpoint');
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/user/%d/uploads/', $this->owner->userid ?? 'me');
    }

    public function destroy(): bool
    {
        throw new ImproperActionException('No DELETE action for this endpoint');
    }

    public function readAll(): array
    {
        $queryParams = new UserUploadsQueryParams(Request::createFromGlobals());
        $idFilter = '';
        if ($this->id) {
            $idFilter = sprintf('AND uploads.id = %d', $this->id);
        }
        $sql = 'SELECT uploads.id, uploads.real_name, uploads.long_name, uploads.created_at, uploads.filesize, uploads.type, uploads.comment,
            COALESCE(experiments.id, items.id, experiments_templates.id) AS entity_id,
            COALESCE(experiments.title, items.title, experiments_templates.title) AS entity_title,
            CASE
                WHEN uploads.type = "experiments" THEN "experiments"
                WHEN uploads.type = "items" THEN "database"
                WHEN uploads.type = "experiments_templates" THEN "ucp"
                ELSE ""
            END AS page
            FROM uploads
            LEFT JOIN experiments ON (uploads.item_id = experiments.id AND uploads.type = "experiments")
            LEFT JOIN items ON (uploads.item_id = items.id AND uploads.type = "items")
            LEFT JOIN experiments_templates ON (uploads.item_id = experiments_templates.id AND uploads.type = "experiments_templates")
            WHERE uploads.userid = :userid AND (uploads.state = :state_normal OR uploads.state = :state_archived) '
            . $idFilter . $queryParams->getSql();
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->owner->userid, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function countAll(): int
    {
        $sql = 'SELECT COUNT(uploads.id) FROM uploads WHERE userid = :userid AND (state = :state_normal OR state = :state_archived)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->owner->userid, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }
}
