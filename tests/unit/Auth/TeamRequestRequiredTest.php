<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use PHPUnit\Framework\TestCase;

final class TeamRequestRequiredTest extends TestCase
{
    public function testKeepsAuthentication(): void
    {
        $authentication = new Authentication(1, AuthMethod::Saml);

        self::assertSame(
            $authentication,
            new TeamRequestRequired($authentication)->authentication,
        );
    }
}
