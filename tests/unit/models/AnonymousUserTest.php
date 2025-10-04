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

use Elabftw\Enums\Language;
use Elabftw\Models\Users\AnonymousUser;

class AnonymousUserTest extends \PHPUnit\Framework\TestCase
{
    public function testAnonymousUser(): void
    {
        $User = new AnonymousUser(1, Language::French);
        $this->assertInstanceOf(AnonymousUser::class, $User);
        $this->assertEquals(1, $User->userData['team']);
        $this->assertEquals('fr_FR', $User->userData['lang']);
    }
}
