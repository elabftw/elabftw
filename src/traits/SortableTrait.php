<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Models\Users;
use PDO;

/**
 * Share updateOrdering for all things that can be reordered
 *
 */
trait SortableTrait
{
    /**
     * Update ordering for status, experiment templates or items types
     *
     * @param Users $users
     * @param array $post POST
     * @return void
     */
    public function updateOrdering(Users $users, array $post): void
    {
        if ($post['table'] === 'todolist') {
            $userOrTeam = 'userid';
            $userOrTeamValue = $users->userData['userid'];
        } else {
            $userOrTeam = 'team';
            $userOrTeamValue = $users->userData['team'];
        }
        // remove the 'Create new' for templates
        if ($post['table'] === 'experiments_templates') {
            unset($post['ordering'][0]);
        }

        foreach ($post['ordering'] as $ordering => $id) {
            $id = explode('_', $id);
            $id = (int) $id[1];
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
