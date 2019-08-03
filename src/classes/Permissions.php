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
     * Get permissions for a database item
     *
     * @return array
     */
    public function forDatabase(): array
    {
        // admin has read/write access to everything in the team
        if ($this->Users->userData['is_admin'] && $this->item['team'] === $this->Users->userData['team']) {
            return array('read' => true, 'write' => true);
        }

        // if we are in same team and visibility is not a group or user, we can read/write fo' shizzle ma nizzle
        if ($this->item['team'] === $this->Users->userData['team'] && (Check::id((int) $this->item['visibility']) === false && $this->item['visibility'] !== 'user')) {
            $ret = array('read' => true, 'write' => true);
            // anon don't get to write anything
            if (isset($this->Users->userData['anon'])) {
                $ret['write'] = false;
            }
            return $ret;
        }
        // ok we are not in the same team as item

        // if the vis. setting is public, we can see it for sure
        if ($this->item['visibility'] === 'public') {
            return array('read' => true, 'write' => false);
        }

        // if it's organization, we need to be logged in
        if (($this->item['visibility'] === 'organization') && $this->Users->userData['userid'] !== null) {
            return array('read' => true, 'write' => false);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        // we also check for anon because anon will have the same team as real team member
        if (($this->item['visibility'] === 'team') &&
            ($this->item['team'] == $this->Users->userData['team']) &&
            !isset($this->Users->userData['anon'])) {
            return array('read' => true, 'write' => true);
        }

        // for user vis. we need to be the user that last edited it
        if (($this->item['visibility'] === 'user') &&
            ($this->item['team'] == $this->Users->userData['team']) &&
            !isset($this->Users->userData['anon']) &&
            ($this->item['userid'] === $this->Users->userData['userid'])) {
            return array('read' => true, 'write' => true);
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['visibility']) !== false) {
            $TeamGroups = new TeamGroups($this->Users);
            if ($TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['visibility'])) {
                return array('read' => true, 'write' => true);
            }
        }
        return array('read' => false, 'write' => false);
    }

    /**
     * Get permissions for an experiment
     *
     * @return array
     */
    public function forExperiments(): array
    {
        $write = false;

        // if we own the experiment, we have read/write rights on it for sure
        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return array('read' => true, 'write' => true);
        }

        // it's not our experiment
        // check if we're admin because admin can read/write all experiments of the team
        if ($this->Users->userData['is_admin'] && $this->item['team'] === $this->Users->userData['team']) {
            return array('read' => true, 'write' => true);
        }

        // if we don't own the experiment (and we are not admin), we need to check if owner allowed edits
        // get the owner data
        $Owner = new Users((int) $this->item['userid']);
        // owner allows edit and is in same team and we are not anon
        if ($Owner->userData['allow_edit'] &&
            $this->item['team'] === $this->Users->userData['team']
            && !isset($this->Users->userData['anon'])) {
            $write = true;
        }

        // if group edits only are accepted
        if ($Owner->userData['allow_group_edit']
            && $this->item['team'] === $this->Users->userData['team']
            && !isset($this->Users->userData['anon'])) {
            $TeamGroups = new TeamGroups($this->Users);
            if ($TeamGroups->isUserInSameGroup((int) $Owner->userData['userid'])) {
                $write = true;
            }
        }

        // if we don't own the experiment (and we are not admin), we need to check the visibility
        // if the vis. setting is public, we can see it for sure
        if ($this->item['visibility'] === 'public') {
            return array('read' => true, 'write' => $write);
        }

        // if it's organization, we need to be logged in
        if (($this->item['visibility'] === 'organization') && $this->Users->userData['userid'] !== null) {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        // we also check for anon because anon will have the same team as real team member
        if (($this->item['visibility'] === 'team') &&
            ($this->item['team'] === $this->Users->userData['team']) &&
            !isset($this->Users->userData['anon'])) {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['visibility']) !== false) {
            $TeamGroups = new TeamGroups($this->Users);
            if ($TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['visibility'])) {
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
