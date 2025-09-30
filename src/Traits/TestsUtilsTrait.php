<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ResourcesCategories;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users\Users;
use PDO;

trait TestsUtilsTrait
{
    protected function getUserInTeam(int $team, int $admin = 0, int $archived = 0): AuthenticatedUser
    {
        $Db = Db::getConnection();
        $sql = 'SELECT * FROM users2teams INNER JOIN users ON users.userid = users2teams.users_id WHERE users.validated = 1 AND teams_id = :team AND is_admin = :admin AND is_archived = :archived LIMIT 1';
        $req = $Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':admin', $admin, PDO::PARAM_INT);
        $req->bindValue(':archived', $archived, PDO::PARAM_INT);
        $Db->execute($req);
        $res = $req->fetch();
        return new AuthenticatedUser($res['users_id'], $team);
    }

    protected function getRandomUserInTeam(int $team, int $admin = 0, int $archived = 0): AuthenticatedUser
    {
        $Db = Db::getConnection();
        $sql = 'SELECT * FROM users2teams INNER JOIN users ON users.userid = users2teams.users_id WHERE users.validated = 1 AND teams_id = :team AND is_admin = :admin AND is_archived = :archived';
        $req = $Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':admin', $admin, PDO::PARAM_INT);
        $req->bindValue(':archived', $archived, PDO::PARAM_INT);
        $Db->execute($req);
        $res = $req->fetchAll();
        $selected = $res[array_rand($res)];
        return new AuthenticatedUser($selected['users_id'], $team);
    }

    protected function getUserIdFromEmail(string $email): int
    {
        $Db = Db::getConnection();
        $sql = 'SELECT userid FROM users WHERE email = :email AND validated = 1 LIMIT 1';
        $req = $Db->prepare($sql);
        $req->bindValue(':email', $email);
        $Db->execute($req);
        return (int) $req->fetchColumn();
    }

    protected function getFreshExperiment(): Experiments
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshExperimentWithGivenUser(Users $users): Experiments
    {
        $Entity = new Experiments($users);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItem(int $team = 1): Items
    {
        $User = $this->getRandomUserInTeam($team);
        $Entity = new Items($User);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItemWithGivenUser(Users $users): Items
    {
        $Entity = new Items($users);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshBookableItem(int $team): Items
    {
        $Item = $this->getFreshItem($team);
        $ResourcesCategories = new ResourcesCategories(new Teams($Item->Users, $team));
        $category = (string) $ResourcesCategories->readAll()[0]['id'];
        $Item->patch(Action::Update, array('is_bookable' => '1', 'category' => $category));
        return $Item;
    }

    protected function getFreshTemplate(): Templates
    {
        $Entity = new Templates(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }
}
