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
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users\Users;
use Elabftw\Services\TeamsHelper;

/**
 * Determine read and write access for a user and an entity
 * Here be dragons! Cognitive load > 9000
 */
final class Permissions
{
    private TeamGroups $TeamGroups;

    private BasePermissions $canreadBase;

    private BasePermissions $canwriteBase;

    // exists only for items
    private ?BasePermissions $canbookBase = null;

    private array $canread;

    private array $canwrite;

    private array $canbook;

    public function __construct(private Users $Users, private array $item)
    {
        $this->TeamGroups = new TeamGroups($this->Users);
        $this->canreadBase = BasePermissions::from($item['canread_base']);
        $this->canwriteBase = BasePermissions::from($item['canwrite_base']);
        $this->canbookBase = isset($item['canbook_base']) ? BasePermissions::from($item['canbook_base']) : null;
        $this->canread = json_decode($item['canread'], true, 512, JSON_THROW_ON_ERROR);
        $this->canwrite = json_decode($item['canwrite'], true, 512, JSON_THROW_ON_ERROR);
        $this->canbook = isset($item['canbook']) ? json_decode($item['canbook'], true, 512, JSON_THROW_ON_ERROR) : array();
    }

    /**
     * Get permissions for an entity
     */
    public function forEntity(): array
    {
        // if we have write access, then we have read access for sure
        if ($this->getWrite()) {
            return array('read' => true, 'write' => true, 'book' => $this->getBook());
        }

        return array(
            'read' => $this->getCan($this->canreadBase, $this->canread),
            'write' => false,
            'book' => $this->getBook(),
        );
    }

    public function getCan(BasePermissions $base, array $can): bool
    {
        // if base permission is public, we can
        if ($base === BasePermissions::Full) {
            return true;
        }

        // starting from here, if we are anon we can't possibly have access
        if ($this->Users instanceof AnonymousUser) {
            return false;
        }

        if ($base === BasePermissions::Organization && $this->Users instanceof AuthenticatedUser) {
            return true;
        }

        // if the base setting is teams, check we are in the same team than the $item
        if ($base === BasePermissions::Team) {
            // items will have a team, make sure it's the same as the one we are logged in
            if (isset($this->item['team']) && ($this->item['team'] === $this->Users->userData['team'])) {
                return true;
            }
        }

        // if the setting is 'user' (meaning user + admin(s)) check we are admin in the same team as the entity team column
        if ($base === BasePermissions::User) {
            $TeamsHelper = new TeamsHelper($this->item['team']);
            if ($this->Users->isAdmin && $TeamsHelper->isAdminInTeam($this->Users->userData['userid'])) {
                return true;
            }
        }

        // check for teams
        if (!empty($can['teams'])) {
            $teamsOfUser = array_column($this->Users->userData['teams'], 'id');
            foreach ($can['teams'] as $team) {
                if (in_array($team, $teamsOfUser, true)) {
                    return true;
                }
            }
        }

        // check for teamgroups
        if (!empty($can['teamgroups'])) {
            foreach ($can['teamgroups'] as $teamgroup) {
                if ($this->TeamGroups->isInTeamGroup($this->Users->userData['userid'], (int) $teamgroup)) {
                    return true;
                }
            }
        }

        // check for users
        if (in_array($this->Users->userData['userid'], $can['users'] ?? array(), true)) {
            return true;
        }

        // if we own the entity, we have access on it for sure
        if ($this->item['userid'] === $this->Users->userData['userid']) {
            return true;
        }

        return false;
    }

    /**
     * Get the write permission for an exp/item
     */
    private function getWrite(): bool
    {
        // locked entity cannot be written to
        // only the locker can unlock an entity
        if (($this->item['locked'] ?? false) && ($this->item['lockedby'] !== $this->Users->userData['userid']) && !$this->Users->isAdmin) {
            return false;
        }
        return $this->getCan($this->canwriteBase, $this->canwrite);
    }

    // Get the booking permission for an item
    private function getBook(): bool
    {
        if ($this->canbookBase === null) {
            return false; // non-bookable entity
        }
        return $this->getCan($this->canbookBase, $this->canbook);
    }
}
