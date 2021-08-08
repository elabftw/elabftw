<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Elabftw;

use function basename;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Services\AnonAuth;
use Elabftw\Services\CookieAuth;
use function in_array;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide methods to authenticate a user
 */
class Auth implements AuthInterface
{
    public function __construct(private Config $Config, private Request $Request)
    {
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

    private function getAuthType(): string
    {
        // try to login with the elabid for an entity in view mode
        $page = basename($this->Request->getScriptName());
        if ($this->Request->query->has('elabid')
            && ($page === 'experiments.php' || $page === 'database.php')
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
                return new CookieAuth((string) $this->Request->cookies->get('token'), $this->Request->cookies->getDigits('token_team'));
            case 'elabid':
                // now we need to know in which team we autologin the user
                // use the page from the request to determine if it's from items or experiments
                $page = $this->Request->getScriptName();
                if ($page === '/experiments.php') {
                    $Entity = new Experiments(new Users(), (int) $this->Request->query->get('id'));
                } elseif ($page === '/database.php') {
                    $Entity = new Items(new Users(), (int) $this->Request->query->get('id'));
                } else {
                    throw new UnauthorizedException();
                }
                $team = $Entity->getTeamFromElabid((string) $this->Request->query->get('elabid'));
                if ($team === 0) {
                    throw new UnauthorizedException();
                }
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
