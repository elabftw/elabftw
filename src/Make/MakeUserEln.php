<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Models\Users;
use ZipStream\ZipStream;

/**
 * Make an ELN archive for a user
 */
class MakeUserEln extends AbstractMakeEln
{
    public bool $skipResources = false;

    public function __construct(ZipStream $Zip, protected Users $user)
    {
        parent::__construct($Zip);
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    public function getStreamZip(): void
    {

        $targets = $this->user->getAllEntitySlugs();
        $Maker = new MakeEln($this->Zip, $this->user, $targets);
        $Maker->getStreamZip();
    }
}
