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

use Elabftw\Enums\Messages;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AppTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    protected function tearDown(): void
    {
        unset($_SERVER['ELABFTW_REQUEST_ID']);
    }

    public function testGetWhatsnewLink(): void
    {
        $this->assertEquals('https://www.deltablot.com/posts/release-50100', App::getWhatsnewLink(50169));
        $this->assertEquals('https://www.deltablot.com/posts/release-66600', App::getWhatsnewLink(66642));
    }

    public function testGetResponseFromAppException(): void
    {
        $_SERVER['ELABFTW_REQUEST_ID'] = 'request-id-app-exception';
        $Exception = new UnauthorizedException('Expected application error');
        $Log = $this->createMock(LoggerInterface::class);
        $Log->expects(self::once())
            ->method('log')
            ->with(
                'notice',
                'Expected application error',
                array(
                    'userid' => 123,
                    'request-id' => 'request-id-app-exception',
                    'exception' => $Exception,
                ),
            );

        $Response = $this->getApp($Log)->getResponseFromException($Exception);
        $content = (string) $Response->getContent();

        self::assertSame(401, $Response->getStatusCode());
        self::assertStringContainsString('Expected application error', $content);
        self::assertStringContainsString('request-id-app-exception', $content);
    }

    public function testGetResponseFromServerAppException(): void
    {
        $Exception = new DatabaseErrorException(array('HY000', 1234, 'Expected database error'));
        $Log = $this->createMock(LoggerInterface::class);
        $Log->expects(self::once())
            ->method('log')
            ->with(
                'error',
                'Expected database error',
                array(
                    'userid' => 123,
                    'request-id' => '',
                    'exception' => $Exception,
                ),
            );

        $Response = $this->getApp($Log)->getResponseFromException($Exception);

        self::assertSame(500, $Response->getStatusCode());
        self::assertStringContainsString('Expected database error', (string) $Response->getContent());
    }

    public function testGetResponseFromUnexpectedException(): void
    {
        $_SERVER['ELABFTW_REQUEST_ID'] = 'request-id-unexpected-exception';
        $Exception = new Exception('Sensitive internal error');
        $Log = $this->createMock(LoggerInterface::class);
        $Log->expects(self::once())
            ->method('error')
            ->with('', array(
                array('userid' => 123),
                array('Exception' => $Exception),
            ));

        $Response = $this->getApp($Log)->getResponseFromException($Exception);
        $content = (string) $Response->getContent();

        self::assertSame(500, $Response->getStatusCode());
        self::assertStringContainsString(Messages::CriticalError->toHuman(), $content);
        self::assertStringContainsString('request-id-unexpected-exception', $content);
        self::assertStringNotContainsString('Sensitive internal error', $content);
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

    private function getApp(LoggerInterface $Log): App
    {
        $Session = new Session(new MockArraySessionStorage());
        $Session->set('userid', 123);
        $App = new App(
            Request::create('/index.php'),
            $Session,
            Config::getConfig(),
            $Log,
            new Users(),
            devMode: true,
        );
        $App->boot();
        return $App;
    }
}
