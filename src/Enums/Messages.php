<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Messages
{
    case CriticalError;
    case DatabaseError;
    case GenericError;
    case InsufficientPermissions;
    case DemoMode;

    public function toHttpCode(): int
    {
        return match ($this) {
            $this::CriticalError => 500,
            $this::DatabaseError => 500,
            $this::GenericError => 400,
            $this::InsufficientPermissions => 403,
            $this::DemoMode => 403,
        };
    }

    public function toHuman(): string
    {
        return match ($this) {
            $this::CriticalError => _('An internal error occurred. This should never happen! Please contact support with the following identifier:'),
            $this::DatabaseError => _('Sorry, there was an issue executing your request. Please try again later.'),
            $this::GenericError => _('An error occurred!'),
            $this::InsufficientPermissions => _('Sorry, you are not allowed to perform that action.'),
            $this::DemoMode => _('Sorry, this action is disabled in demo mode.'),
        };
    }
}
