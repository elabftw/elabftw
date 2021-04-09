<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Interfaces;

interface ItemTypeParamsInterface extends ContentParamsInterface
{
    public function getBody(): string;

    public function getCanread(): string;

    // TODO named like this because clash with the canwrite from apikey that returns an int
    public function getCanwriteS(): string;

    public function getColor(): string;

    public function getIsBookable(): int;

    public function getTeam(): int;
}
