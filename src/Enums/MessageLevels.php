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

enum MessageLevels: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Error = 'ko';

    public function toFaIcon(): string
    {
        return match ($this) {
            self::Ok => 'fa-info-circle',
            self::Warning => 'fa-chevron-right',
            self::Error => 'fa-exclamation-triangle',
        };
    }

    public function toAlertClass(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Warning => 'warning',
            self::Error => 'danger',
        };
    }
}
