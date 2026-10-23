<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum BookingApprovalStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
    case Expired = 3;
}
