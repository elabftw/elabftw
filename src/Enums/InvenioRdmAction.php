<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Karl Krägelin
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Enums;

enum InvenioRdmAction: string
{
    case GetCommunities  = 'getcommunities';
    case GetResourceTypes = 'getresourcetypes';
    case GetLicenses     = 'getlicenses';
}
