<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Traits\TestsUtilsTrait;

class AppTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testGetWhatsnewLink(): void
    {
        $this->assertEquals('https://www.deltablot.com/posts/release-50100', App::getWhatsnewLink(50169));
        $this->assertEquals('https://www.deltablot.com/posts/release-66600', App::getWhatsnewLink(66642));
    }

    public function testResolveThemeClassAnonDark(): void
    {
        $this->assertSame('dark-mode', App::resolveThemeClass(null, 'dark'));
    }

    public function testResolveThemeClassAuthenticatedDark(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $user->userData['dark_mode'] = 1;
        $this->assertSame('dark-mode', App::resolveThemeClass($user, 'light'));
    }

    public function testResolveThemeClassAuthenticatedLight(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $user->userData['dark_mode'] = 0;
        $this->assertSame('', App::resolveThemeClass($user, 'dark'));
    }

    public function testResolveThemeClassAuthenticatedNoPref(): void
    {
        $user = $this->getRandomUserInTeam(1);
        unset($user->userData['dark_mode']);
        $this->assertSame('', App::resolveThemeClass($user, 'dark'));
    }
}
