<?php

declare(strict_types=1);

namespace Elabftw\Enums;

enum BookingApprovalStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
    case Expired = 3;
}
