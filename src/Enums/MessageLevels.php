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
            $this::Ok => 'fa-info-circle',
            $this::Warning => 'fa-chevron-right',
            $this::Error => 'fa-exclamation-triangle',
        };
    }

    public function toAlertClass(): string
    {
        return match ($this) {
            $this::Ok => 'success',
            $this::Warning => 'warning',
            $this::Error => 'danger',
        };
    }
}
