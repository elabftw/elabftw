<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determine read and write access for a user and an entity
 * Here be dragons! Cognitive load > 9000
 */
class Permissions
{
    /** @var Users $Users instance of Users */
    private $Users;

    /** @var array $item the item to check */
    private $item;

    /**
     * Constructor
     *
     * @param Users $users
     * @param array $item
     */
    public function __construct(Users $users, array $item)
    {
        $this->Users = $users;
        $this->item = $item;
    }

    /**
     * Get permissions for an experiment/item
     *
     * @return array
     */
    public function forExpItem(): array
    {
        $write = false;

        // if we own the experiment, we have read/write rights on it for sure
        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return array('read' => true, 'write' => true);
        }

        // it's not our experiment
        // get the owner data
        $Owner = new Users((int) $this->item['userid']);
        $TeamGroups = new TeamGroups($this->Users);

        // check if we're admin because admin can read/write all experiments of the team
        if ($this->Users->userData['is_admin'] && $Owner->userData['team'] === $this->Users->userData['team']) {
            return array('read' => true, 'write' => true);
        }

        // if we don't own it (and we are not admin), we need to check if owner allowed edits
        // owner allows edit and is in same team and we are not anon
        if ($this->item['canwrite'] === 'public') {
            $write = true;
        }
        if (($this->item['canwrite'] === 'organization')
            && !isset($this->Users->userData['anon'])) {
            $write = true;
        }
        if (($this->item['canwrite'] === 'team')
            && !isset($this->Users->userData['anon'])) {
            // check if we have a team in common
            $Teams = new Teams($this->Users);
            if ($Teams->hasCommonTeam((int) $this->item['userid'], (int) $this->Users->userData['userid'])) {
                $write = true;
            }
        }
        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['canwrite']) !== false) {
            if ($TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['canwrite'])) {
                $write = true;
            }
        }

        // if we can write to it, we can read it too, so return early
        if ($write === true) {
            return array('read' => true, 'write' => true);
        }

        // OK we cannot write to it, check for read permission now

        // if we don't own the experiment (and we are not admin), we need to check read access
        // if it is public, we can see it for sure
        if ($this->item['canread'] === 'public') {
            return array('read' => true, 'write' => $write);
        }

        // if it's organization, we need to be logged in
        if (($this->item['canread'] === 'organization') && $this->Users->userData['userid'] !== null) {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        // we also check for anon because anon will have the same team as real team member
        if (($this->item['canread'] === 'team') &&
            !isset($this->Users->userData['anon'])) {
            // ok so we need to check if the team(s) in which the owner is match the team(s) of our current user
            $Teams = new Teams($this->Users);
            if ($Teams->hasCommonTeam((int) $this->item['userid'], (int) $this->Users->userData['userid'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['canread']) !== false) {
            if ($TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['canread'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // if we have the elabid in the URL, allow read access to all
        $Request = Request::createFromGlobals();
        if ($this->item['elabid'] === $Request->query->get('elabid')) {
            return array('read' => true, 'write' => $write);
        }

        return array('read' => false, 'write' => $write);
    }

    /**
     * Get permissions for a template
     *
     * @return array
     */
    public function forTemplates(): array
    {
        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return array('read' => true, 'write' => true);
        }
        return array('read' => false, 'write' => false);
    }
}
