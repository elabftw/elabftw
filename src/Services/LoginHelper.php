<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\AuditEvent\UserLogin;
use Elabftw\Auth\AnonymousLoginContext;
use Elabftw\Auth\CookieToken;
use Elabftw\Auth\UserLoginContext;
use Elabftw\Elabftw\BuildInfo;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuthMethod;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Notifications\NewVersionInstalled;
use Elabftw\Models\Users\Users;
use PDO;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function time;
use function setcookie;

/**
 * Methods to login the user (once the authentication is done)
 */
final class LoginHelper
{
    private Db $Db;

    public function __construct(private UserLoginContext|AnonymousLoginContext $context, private SessionInterface $Session, private int $cookieValidityTime)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Login means having some anon / auth in session + team + userid
     * and set the cookie "token" if it was requested
     */
    public function login(bool $setCookie = false): void
    {
        $this->Session->migrate(true);

        $this->populateSession();

        if ($setCookie) {
            $this->setToken();
        }

        // if we run a version newer than the last time the user logged in, create a notification
        // but only if it's a minor version
        if (
            BuildInfo::VERSION_INT - $this->getLastSeenVersion() >= 100
            && !$this->context instanceof AnonymousLoginContext
        ) {
            $authUser = new Users($this->context->getUserid());
            new NewVersionInstalled($authUser)->create();
        }

        if (!$this->context instanceof AnonymousLoginContext) {
            $this->updateLast();

            if ($this->context->getAuthMethod() !== AuthMethod::Cookie) {
                $this->updateAuthService();
            }
        }

        $this->setDeviceToken();

        AuditLogs::create(
            new UserLogin(
                $this->context->getUserid(),
                $this->context->getUserid(),
            ),
        );
    }

    public function getCookieExpiryTimestamp(): int
    {
        return time() + 60 * $this->cookieValidityTime;
    }

    /**
     * Update last login time and last seen version.
     */
    private function updateLast(): void
    {
        $sql = 'UPDATE users
            SET last_login = NOW(), last_seen_version = :version
            WHERE userid = :userid';

        $req = $this->Db->prepare($sql);
        $req->bindValue(
            ':userid',
            $this->context->getUserid(),
            PDO::PARAM_INT,
        );
        $req->bindValue(
            ':version',
            BuildInfo::VERSION_INT,
            PDO::PARAM_INT,
        );

        $this->Db->execute($req);
    }

    /**
     * Update the authentication service used.
     */
    private function updateAuthService(): void
    {
        $sql = 'UPDATE users
            SET auth_service = :auth_service
            WHERE userid = :userid';

        $req = $this->Db->prepare($sql);
        $req->bindValue(
            ':userid',
            $this->context->getUserid(),
            PDO::PARAM_INT,
        );
        $req->bindValue(
            ':auth_service',
            $this->context->getAuthMethod()->value,
            PDO::PARAM_INT,
        );

        $this->Db->execute($req);
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Also set a token_team cookie for the team
     */
    private function setToken(): void
    {
        $CookieToken = CookieToken::fromScratch();
        $CookieToken->saveToken($this->context->getUserid());

        $cookieOptions = array(
            'expires' => $this->getCookieExpiryTimestamp(),
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie('token', $CookieToken->getToken(), $cookieOptions);
        setcookie('token_team', (string) $this->context->getTeam(), $cookieOptions);
    }

    private function getLastSeenVersion(): int
    {
        $sql = 'SELECT last_seen_version FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->context->getUserid(), PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function setDeviceToken(): void
    {
        // set device token as a cookie
        $cookieOptions = array(
            'expires' => time() + DeviceToken::DEFAULT_LIFETIME_SECONDS,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );

        setcookie('devicetoken', DeviceToken::getToken($this->context->getUserid()), $cookieOptions);
    }

    private function populateSession(): void
    {
        // Main switch to know if we are logged in
        $this->Session->set('is_auth', 1);

        // ANY LOGIN needs to have a team
        $this->Session->set('team', $this->context->getTeam());

        // ANON will get userid 0 here
        $this->Session->set('userid', $this->context->getUserid());

        // add this flag to discriminate between normal user and anonymous user
        if ($this->context->isAnonymous()) {
            $this->Session->set('is_anon', 1);
        }
    }
}
