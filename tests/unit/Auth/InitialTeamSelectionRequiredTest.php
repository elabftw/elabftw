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

use PHPUnit\Framework\TestCase;

final class InitialTeamSelectionRequiredTest extends TestCase
{
    public function testToArray(): void
    {
        $selection = new InitialTeamSelectionRequired(
            email: 'user@example.com',
            firstname: 'Marie',
            lastname: 'Curie',
            orgid: 'org-123',
            orcid: '0000-0002-1825-0097',
        );

        self::assertSame(
            array(
                'email' => 'user@example.com',
                'firstname' => 'Marie',
                'lastname' => 'Curie',
                'orgid' => 'org-123',
                'orcid' => '0000-0002-1825-0097',
            ),
            $selection->toArray(),
        );
    }

    public function testOptionalIdentifiersDefaultToNull(): void
    {
        $selection = new InitialTeamSelectionRequired(
            email: 'user@example.com',
            firstname: 'Marie',
            lastname: 'Curie',
        );

        self::assertNull($selection->orgid);
        self::assertNull($selection->orcid);
    }
}
