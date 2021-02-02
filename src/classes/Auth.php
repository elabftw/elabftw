<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function basename;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Services\AnonAuth;
use Elabftw\Services\CookieAuth;
use Elabftw\Services\SessionAuth;
use function in_array;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide methods to login a user
 */
class Auth implements AuthInterface
{
    private Config $Config;

    private SessionInterface $Session;

    private Request $Request;

    public function __construct(App $app)
    {
        $this->Config = $app->Config;
        $this->Request = $app->Request;
        $this->Session = $app->Session;
    }

    /**
     * Try to authenticate with session and cookie
     *     ____          _
     *    / ___|___ _ __| |__   ___ _ __ _   _ ___
     *   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|
     *   | |___  __/ |  | |_) |  __/ |  | |_| \__ \
     *    \____\___|_|  |_.__/ \___|_|   \__,_|___/
     *
     */
    public function tryAuth(): AuthResponse
    {
        $AuthService = $this->getAuthService($this->getAuthType());
        return $AuthService->tryAuth();
    }

    /**
     * Increase the failed attempts counter
     */
    public function increaseFailedAttempt(): void
    {
        if (!$this->Session->has('failed_attempt')) {
            $this->Session->set('failed_attempt', 1);
        } else {
            $n = $this->Session->get('failed_attempt');
            $n++;
            $this->Session->set('failed_attempt', $n);
        }
    }

    /**
     * Check if we need to bother with authentication of current user
     *
     * @return bool True if we are authentified (or if we don't need to be)
     */
    public function needAuth(): bool
    {
        // pages where you don't need to be logged in
        // only the script name, not the path because we use basename() on it
        $nologinArr = array(
            'change-pass.php',
            'index.php',
            'login.php',
            'LoginController.php',
            'metadata.php',
            'register.php',
            'RegisterController.php',
            'ResetPasswordController.php',
        );

        return !in_array(basename($this->Request->getScriptName()), $nologinArr, true);
    }

    private function getAuthType(): string
    {
        // if we are already logged in with the session, skip everything
        // same if we don't need to be authenticated
        if ($this->Session->has('is_auth')) {
            return 'session';
        }

        // try to login with the elabid for an experiment in view mode
        if ($this->Request->query->has('elabid')
            && basename($this->Request->getScriptName()) === 'experiments.php'
            && $this->Request->query->get('mode') === 'view') {
            return 'elabid';
        }

        // try to login with the cookie if we have one in the request
        if ($this->Request->cookies->has('token')) {
            return 'cookie';
        }

        // autologin as anon if it's allowed by sysadmin
        if ($this->Config->configArr['open_science']) {
            return 'open';
        }
        throw new UnauthorizedException();
    }

    private function getAuthService(string $authType): AuthInterface
    {
        switch ($authType) {
            // AUTH WITH COOKIE
            case 'cookie':
                return new CookieAuth($this->Request->cookies->get('token'), $this->Request->cookies->get('token_team'));
            case 'session':
                return new SessionAuth();
            case 'elabid':
                // now we need to know in which team we autologin the user
                $Experiments = new Experiments(new Users(), (int) $this->Request->query->get('id'));
                $team = $Experiments->getTeamFromElabid($this->Request->query->get('elabid'));
                return new AnonAuth($this->Config->configArr, $team);
            case 'open':
                // don't do it if we have elabid in url
                // only autologin on selected pages and if we are not authenticated with an account
                $autoAnon = array('experiments.php', 'database.php', 'search.php');
                if (in_array(basename($this->Request->getScriptName()), $autoAnon, true)) {
                    return new AnonAuth($this->Config->configArr, (int) ($this->Config->configArr['open_team'] ?? 1));
                }
                // no break
            default:
                throw new UnauthorizedException();

        }
    }
}
