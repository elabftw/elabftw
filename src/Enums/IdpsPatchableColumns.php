<?php

/**
 * @author Nicolas CARPi - Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum IdpsPatchableColumns: string
{
    case Name = 'name';
    case EntityId = 'entityid';
    case Enabled = 'enabled';
    case Source = 'source';
    case EmailAttr = 'email_attr';
    case TeamAttr = 'team_attr';
    case FnameAttr = 'fname_attr';
    case LnameAttr = 'lname_attr';
    case OrgidAttr = 'orgid_attr';
}
