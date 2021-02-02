<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
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
    private Users $Users;

    private Teams $Teams;

    private TeamGroups $TeamGroups;

    private array $item;

    /**
     * Constructor
     *
     * @param Users $users
     * @param array<string, mixed> $item
     */
    public function __construct(Users $users, array $item)
    {
        $this->Users = $users;
        $this->item = $item;
        $this->Teams = new Teams($this->Users);
        $this->TeamGroups = new TeamGroups($this->Users);
    }

    /**
     * Get permissions for an experiment/item
     */
    public function forExpItem(): array
    {
        $write = $this->getWrite();

        // if we have write access, then we have read access for sure
        if ($write) {
            return array('read' => true, 'write' => $write);
        }

        // if it's public, we can read it
        if ($this->item['canread'] === 'public') {
            return array('read' => true, 'write' => $write);
        }

        // if we have the elabid in the URL, allow read access to all
        $Request = Request::createFromGlobals();
        // make sure we check if entity has elabid because items won't have one (null)
        if (isset($this->item['elabid']) && ($this->item['elabid'] === $Request->query->get('elabid'))) {
            return array('read' => true, 'write' => $write);
        }

        // starting from here, if we are anon we can't possibly have read access
        if (isset($this->Users->userData['anon'])) {
            return array('read' => false, 'write' => false);
        }

        if ($this->item['canread'] === 'organization') {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        if ($this->item['canread'] === 'team') {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ((int) $this->item['team'] === $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
            // check if we have a team in common
            if ($this->Teams->hasCommonTeamWithCurrent((int) $this->item['userid'], $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['canread']) !== false && $this->TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['canread'])) {
            return array('read' => true, 'write' => $write);
        }
        return array('read' => false, 'write' => false);
    }

    /**
     * Get permissions for a template
     */
    public function forTemplates(): array
    {
        $write = $this->getWrite();

        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return array('read' => true, 'write' => true);
        }

        // if it's public, we can read it
        if ($this->item['canread'] === 'public') {
            return array('read' => true, 'write' => $write);
        }

        // starting from here, if we are anon we can't possibly have read access
        if (isset($this->Users->userData['anon'])) {
            return array('read' => false, 'write' => false);
        }

        if ($this->item['canread'] === 'organization') {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        if ($this->item['canread'] === 'team') {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ((int) $this->item['team'] === $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
            // check if we have a team in common
            if ($this->Teams->hasCommonTeamWithCurrent((int) $this->item['userid'], $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['canread']) !== false) {
            if ($this->TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['canread'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        return array('read' => false, 'write' => false);
    }

    /**
     * Get the write permission for an exp/item
     */
    private function getWrite(): bool
    {
        // if anyone can write, we're sure to have access
        if ($this->item['canwrite'] === 'public') {
            return true;
        }

        // starting from here, if we are anon we can't possibly have write access
        if (isset($this->Users->userData['anon'])) {
            return false;
        }

        // if any logged in user can write, we can as we are not anon
        if ($this->item['canwrite'] === 'organization') {
            return true;
        }

        if ($this->item['canwrite'] === 'team') {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ((int) $this->item['team'] === $this->Users->userData['team'])) {
                return true;
            }
            // check if we have a team in common
            if ($this->Teams->hasCommonTeamWithCurrent((int) $this->item['userid'], $this->Users->userData['team'])) {
                return true;
            }
        }

        // if the vis. setting is a team group, check we are in the group
        if (Check::id((int) $this->item['canwrite']) !== false && $this->TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $this->item['canwrite'])) {
            return true;
        }

        // if we own the entity, we have write access on it for sure
        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return true;
        }

        // it's not our entity, our last chance is to be admin in the same team as owner
        if ($this->Users->userData['is_admin']) {
            // if it's an item (has team attribute), we need to be logged in in same team
            if (isset($this->item['team'])) {
                if ((int) $this->item['team'] === $this->Users->userData['team']) {
                    return true;
                }
            } else { // experiment
                $Owner = new Users((int) $this->item['userid']);
                if ($this->Teams->hasCommonTeamWithCurrent((int) $Owner->userData['userid'], $this->Users->userData['team'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
