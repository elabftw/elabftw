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

use Elabftw\Enums\BasePermissions;
use Elabftw\Models\AnonymousUser;
use Elabftw\Models\AuthenticatedUser;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determine read and write access for a user and an entity
 * Here be dragons! Cognitive load > 9000
 */
class Permissions
{
    private Teams $Teams;

    private TeamGroups $TeamGroups;

    private array $canread;

    private array $canwrite;

    /**
     * Constructor
     *
     * @param array<string, mixed> $item
     */
    public function __construct(private Users $Users, private array $item)
    {
        $this->Teams = new Teams($this->Users);
        $this->TeamGroups = new TeamGroups($this->Users);
        $this->canread = json_decode($item['canread'], true, 512, JSON_THROW_ON_ERROR);
        $this->canwrite = json_decode($item['canwrite'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Get permissions for an entity
     */
    public function forEntity(): array
    {
        $write = $this->getWrite();

        // if we have write access, then we have read access for sure
        if ($write) {
            return array('read' => true, 'write' => $write);
        }

        // if it's public, we can read it
        if ($this->canread['base'] === BasePermissions::Full->value) {
            return array('read' => true, 'write' => $write);
        }

        // if we have the elabid in the URL, allow read access to all
        $Request = Request::createFromGlobals();
        if (($this->item['elabid'] ?? '') === $Request->query->get('elabid')) {
            return array('read' => true, 'write' => $write);
        }

        // starting from here, if we are anon we can't possibly have read access
        if ($this->Users instanceof AnonymousUser) {
            return array('read' => false, 'write' => false);
        }

        if ($this->canread['base'] === BasePermissions::Organization->value && $this->Users instanceof AuthenticatedUser) {
            return array('read' => true, 'write' => $write);
        }

        // if the vis. setting is team, check we are in the same team than the $item
        if ($this->canread['base'] === BasePermissions::MyTeams->value) {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ($this->item['team'] === $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
            // check if we have a team in common
            if ($this->Teams->hasCommonTeamWithCurrent($this->item['userid'], (int) $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // if the setting is 'user' (meaning user + admin(s)) check we are admin
        if ($this->canread['base'] === BasePermissions::User->value) {
            if ($this->Users->userData['is_admin'] && $this->Teams->hasCommonTeamWithCurrent($this->item['userid'], (int) $this->Users->userData['team'])) {
                return array('read' => true, 'write' => $write);
            }
        }

        // check for teams
        if (!empty($this->canread['teams'])) {
            $UsersHelper = new UsersHelper((int) $this->Users->userData['userid']);
            $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
            foreach ($this->canread['teams'] as $team) {
                if (in_array($team, $teamsOfUser, true)) {
                    return array('read' => true, 'write' => $write);
                }
            }
        }

        // check for teamgroups
        if (!empty($this->canread['teamgroups'])) {
            foreach ($this->canread['teamgroups'] as $teamgroup) {
                if ($this->TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $teamgroup)) {
                    return array('read' => true, 'write' => $write);
                }
            }
        }

        // check for users
        if (in_array((int) $this->Users->userData['userid'], $this->canread['users'], true)) {
            return array('read' => true, 'write' => $write);
        }

        return array('read' => false, 'write' => false);
    }

    /**
     * For ItemType write permission check for metadata
     */
    public function forItemType(): array
    {
        if ($this->Users->userData['is_admin'] && ($this->item['team'] === $this->Users->userData['team'])) {
            return array('read' => true, 'write' => true);
        }
        return array('read' => false, 'write' => false);
    }

    /**
     * Get the write permission for an exp/item
     */
    private function getWrite(): bool
    {
        // locked entity cannot be written to
        // only the locker can unlock an entity
        if ($this->item['locked'] && ($this->item['lockedby'] !== (int) $this->Users->userData['userid']) && !$this->Users->userData['is_admin']) {
            return false;
        }

        // if anyone can write, we're sure to have access
        if ($this->canwrite['base'] === BasePermissions::Full->value) {
            return true;
        }

        // starting from here, if we are anon we can't possibly have write access
        if ($this->Users instanceof AnonymousUser) {
            return false;
        }

        // if any logged in user can write, we can as we are not anon
        if ($this->canwrite['base'] === BasePermissions::Organization->value && $this->Users instanceof AuthenticatedUser) {
            return true;
        }

        if ($this->canwrite['base'] === BasePermissions::MyTeams->value) {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ($this->item['team'] === $this->Users->userData['team'])) {
                return true;
            }
            // check if we have a team in common
            if ($this->Teams->hasCommonTeamWithCurrent($this->item['userid'], (int) $this->Users->userData['team'])) {
                return true;
            }
        }

        // check for teams
        if (!empty($this->canread['teams'])) {
            $UsersHelper = new UsersHelper((int) $this->Users->userData['userid']);
            $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
            foreach ($this->canread['teams'] as $team) {
                if (in_array($team, $teamsOfUser, true)) {
                    return true;
                }
            }
        }

        // check for teamgroups
        if (!empty($this->canwrite['teamgroups'])) {
            foreach ($this->canwrite['teamgroups'] as $teamgroup) {
                if ($this->TeamGroups->isInTeamGroup((int) $this->Users->userData['userid'], (int) $teamgroup)) {
                    return true;
                }
            }
        }

        // check for users
        if (in_array((int) $this->Users->userData['userid'], $this->canwrite['users'], true)) {
            return true;
        }

        // if we own the entity, we have write access on it for sure
        if ($this->item['userid'] === (int) $this->Users->userData['userid']) {
            return true;
        }

        // it's not our entity, our last chance is to be admin in the same team as owner
        // also make sure that it's not in "useronly" mode
        if ($this->Users->userData['is_admin'] && $this->canwrite['base'] !== BasePermissions::UserOnly->value) {
            // if it's an item (has team attribute), we need to be logged in in same team
            if (isset($this->item['team'])) {
                if ($this->item['team'] === $this->Users->userData['team']) {
                    return true;
                }
            } else { // experiment
                $Owner = new Users($this->item['userid']);
                if ($this->Teams->hasCommonTeamWithCurrent((int) $Owner->userData['userid'], (int) $this->Users->userData['team'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
