<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

/**
 * Make a full JSON export, including all information from one or several entities
 */
class MakeFullJson extends MakeJson
{
    protected function getEntityData(): array
    {
        return $this->Entity->readOneFull();
    }
}
