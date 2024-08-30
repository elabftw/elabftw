<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Usergroup;

class ValidatedUserTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateFromAdmin(): void
    {
        $User = ValidatedUser::fromAdmin(
            'validateduser@example.com',
            array('Alpha'),
            'valid',
            'user',
            Usergroup::User,
        );
        $this->assertInstanceOf(ExistingUser::class, $User);
    }
}
