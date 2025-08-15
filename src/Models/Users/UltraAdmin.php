<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Users;

/**
 * A user interacting with the app from CLI, so has full rights on everything
 */
final class UltraAdmin extends Users
{
    public function __construct(public ?int $userid = null, public ?int $team = null)
    {
        $this->userData['is_sysadmin'] = 1;
        $this->userData['userid'] = $userid;
        $this->userData['team'] = $team;
        $this->userData['scope_events'] = 3;
        $this->userData['scope_experiments'] = 3;
        $this->userData['scope_experiments_templates'] = 3;
        $this->userData['scope_items'] = 3;
        $this->userData['scope_items_types'] = 3;
        $this->userData['scope_teamgroups'] = 3;
    }
}
