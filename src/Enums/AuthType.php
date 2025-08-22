<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use RuntimeException;

enum AuthType: string
{
    case Anonymous = 'anon';
    case Demo = 'demo';
    case External = 'external';
    case Ldap = 'ldap';
    case Local = 'local';
    case Mfa = 'mfa';
    case Saml = 'saml';
    case Team = 'team';
    case TeamInit = 'teaminit';
    case TeamSelection = 'teamselection';

    public function asService(): int
    {
        return match ($this) {
            self::Anonymous => 50,
            self::Demo => 5,
            self::External => 40,
            self::Ldap => 30,
            self::Local => 10,
            self::Saml => 20,
            default => throw new RuntimeException('Invalid AuthType enum to get service from'),
        };

    }
}
