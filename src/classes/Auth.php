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
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Services\AnonAuth;
use Elabftw\Services\CookieAuth;
use Elabftw\Services\LoginHelper;
use function in_array;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide methods to login a user
 */
class Auth
{
    /** @var Config $Config */
    private $Config;

    /** @var SessionInterface $Session the current session */
    private $Session;

    /** @var Request $Request current request */
    private $Request;

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
     * @return bool true if we are authenticated
     */
    public function tryAuth(): bool
    {
        // if we are already logged in with the session, skip everything
        // same if we don't need to be authenticated
        if ($this->Session->has('is_auth') || !$this->needAuth()) {
            return true;
        }

        // autologin as anon if it's allowed by sysadmin
        // don't do it if we have elabid in url
        if ($this->Config->configArr['open_science'] && !$this->Request->query->has('elabid')) {
            // only autologin on selected pages and if we are not authenticated with an account
            $autoAnon = array('experiments.php', 'database.php', 'search.php');
            if (in_array(basename($this->Request->getScriptName()), $autoAnon, true)) {
                $AuthService = new AnonAuth($this->Config, (int) $this->Config->configArr['open_team'] ?? 1);
            }
        }
        // try to login with the cookie if we have one in the request
        if ($this->Request->cookies->has('token')) {
            $AuthService = new CookieAuth($this->Request->cookies->get('token'), $this->Request->cookies->get('token_team'));

        // try to login with the elabid for an experiment in view mode
        } elseif ($this->Request->query->has('elabid')
            && basename($this->Request->getScriptName()) === 'experiments.php'
            && $this->Request->query->get('mode') === 'view') {

            // now we need to know in which team we autologin the user
            $Experiments = new Experiments(new Users(), (int) $this->Request->query->get('id'));
            $team = $Experiments->getTeamFromElabid($this->Request->query->get('elabid'));
            $AuthService = new AnonAuth($this->Config, $team);
        }


        if (isset($AuthService)) {
            $AuthResponse = $AuthService->tryAuth();
            $LoginHelper = new LoginHelper($AuthResponse, $this->Session);
            $LoginHelper->login(false);
            return true;
        }

        return false;
    }

    /**
     * Increase the failed attempts counter
     *
     * @return void
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
    private function needAuth(): bool
    {
        // pages where you don't need to be logged in
        // only the script name, not the path because we use basename() on it
        $nologinArr = array(
            'change-pass.php',
            'download.php',
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
}
