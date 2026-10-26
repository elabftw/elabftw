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
    case NoApproval = 0;
    case Pending = 1;
    case Approved = 2;
    case Rejected = 3;
}
