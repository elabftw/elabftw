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

use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AppTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testGetWhatsnewLink(): void
    {
        $this->assertEquals('https://www.deltablot.com/posts/release-50100', App::getWhatsnewLink(50169));
        $this->assertEquals('https://www.deltablot.com/posts/release-66600', App::getWhatsnewLink(66642));
    }

    public function testArchivedUserSessionIsRejected(): void
    {
        $User = $this->getRandomUserInTeam(1);
        $Session = new Session(new MockArraySessionStorage());
        $Session->set('is_auth', 1);
        $Session->set('userid', $User->userid);
        $Session->set('team', 1);
        $Db = Db::getConnection();
        $Db->beginTransaction();

        try {
            $req = $Db->prepare('UPDATE users2teams SET is_archived = 1 WHERE users_id = :userid AND teams_id = :team');
            $req->bindValue(':userid', $User->userid, PDO::PARAM_INT);
            $req->bindValue(':team', 1, PDO::PARAM_INT);
            $Db->execute($req);

            $App = new App(
                Request::create('/index.php'),
                $Session,
                Config::getConfig(),
                App::getDefaultLogger(),
                new Users(),
            );
            try {
                $App->boot();
                self::fail('Expected archived user session to be rejected.');
            } catch (UnauthorizedException) {
                self::assertFalse($Session->has('is_auth'));
            }
        } finally {
            $Db->rollBack();
        }
    }
}
