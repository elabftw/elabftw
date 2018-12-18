<?php
/**
 * \Elabftw\Elabftw\EntityTrait
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use PDO;

/**
 * For things that are used by experiments, database, status, item types, templates, …
 *
 */
trait EntityTrait
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Users $Users our user */
    public $Users;

    /** @var int|null $id Id of the entity */
    public $id;

    /**
     * Check and set id
     *
     * @param int $id
     * @throws IllegalActionException
     * @return void
     */
    public function setId(int $id): void
    {
        if (Tools::checkId($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }
        $this->id = $id;
        // prevent reusing of old data from previous id
        unset($this->entityData);
    }

    /**
     * Update ordering for status, experiment templates or items types
     *
     * @param array $post POST
     * @return void
     */
    public function updateOrdering(array $post): void
    {
        // whitelist the tables
        $whitelist = array(
            'status',
            'experiments_templates',
            'items_types',
            'todolist'
        );

        if (!in_array($post['table'], $whitelist, true)) {
            throw new IllegalActionException('Wrong table supplied for update ordering.');
        }

        if ($post['table'] === 'todolist') {
            $userOrTeam = 'userid';
            $userOrTeamValue = $this->Users->userid;
        } else {
            $userOrTeam = 'team';
            $userOrTeamValue = $this->Users->userData['team'];
        }

        foreach ($post['ordering'] as $ordering => $id) {
            $id = explode('_', $id);
            $id = $id[1];
            // the table param is whitelisted here
            $sql = "UPDATE " . $post['table'] . " SET ordering = :ordering WHERE id = :id AND " . $userOrTeam . " = :userOrTeam";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':userOrTeam', $userOrTeamValue);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
        }
    }
}
