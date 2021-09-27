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
use function bindtextdomain;
use function dirname;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AnonymousUser;
use Elabftw\Models\AuthenticatedUser;
use Elabftw\Models\Config;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Traits\TwigTrait;
use Elabftw\Traits\UploadTrait;
use function in_array;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Fs;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use function putenv;
use function setlocale;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use function textdomain;

/**
 * This is a super class holding various global objects
 */
class App
{
    use UploadTrait;

    use TwigTrait;

    public const INSTALLED_VERSION = '4.1.0-BETA4';

    public Users $Users;

    public string $pageTitle = 'Lab manager';

    public string $linkName = 'Documentation';

    public string $linkHref = 'https://doc.elabftw.net';

    public array $ok = array();

    public array $ko = array();

    public array $warning = array();

    public function __construct(public Request $Request, public SessionInterface $Session, public Config $Config, public Logger $Log)
    {
        $flashBag = $this->Session->getBag('flashes');
        // add type check because SessionBagInterface doesn't have get(), only FlashBag has it
        if ($flashBag instanceof FlashBag) {
            $this->ok = $flashBag->get('ok');
            $this->ko = $flashBag->get('ko');
            $this->warning = $flashBag->get('warning');
        }

        $this->Log->pushHandler(new ErrorLogHandler());
        $this->Users = new Users();
        // UPDATE SQL SCHEMA if necessary or show error message if version mismatch
        $Update = new Update((int) $this->Config->configArr['schema'], new Sql(new Fs(new Local(dirname(__DIR__) . '/sql'))));
        $Update->checkSchema();
    }

    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-//
    //     _                 _      //
    //    | |__   ___   ___ | |_    //
    //    | '_ \ / _ \ / _ \| __|   //
    //    | |_) | (_) | (_) | |_    //
    //    |_.__/ \___/ \___/ \__|   //
    //                              //
    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-//
    public function boot(): void
    {
        // load the Users with a userid if we are auth and not anon
        if ($this->Session->has('is_auth') && $this->Session->get('userid') !== 0) {
            $this->loadUser(new AuthenticatedUser(
                $this->Session->get('userid'),
                $this->Session->get('team'),
            ));
        }

        // ANONYMOUS
        if ($this->Session->get('is_anon') === 1) {
            // anon user only has access to a subset of pages
            $allowedPages = array(
                'database.php',
                'download.php',
                'experiments.php',
                'index.php',
                'logout.php',
                'make.php',
                'RequestHandler.php',
                'search.php',
            );
            if (!in_array(basename($this->Request->getScriptName()), $allowedPages, true)) {
                throw new UnauthorizedException();
            }

            $this->loadUser(new AnonymousUser(
                $this->Session->get('team'),
                $this->getLang(),
            ));
        }

        $this->initi18n();
    }

    /**
     * Generate HTML from a twig template. The App object is injected into every template.
     *
     * @param string $template template located in app/tpl/
     * @param array<string, mixed> $variables the variables injected in the template
     * @return string html
     */
    public function render(string $template, array $variables): string
    {
        return $this->getTwig($this->Config)->render($template, array_merge(array('App' => $this), $variables));
    }

    /**
     * Get the lang of our current user
     */
    public function getLang(): string
    {
        // if we have an authenticated user, use their lang setting
        if ($this->Users instanceof AuthenticatedUser) {
            return $this->Users->userData['lang'];
        }
        // default lang is the server configured one
        return $this->Config->configArr['lang'];
    }

    /**
     * Load a user object in our user field
     */
    private function loadUser(AuthenticatedUser | AnonymousUser $users): void
    {
        $this->Users = $users;
        // we have an user in a team, load the top menu link
        $Teams = new Teams($this->Users);
        $teamConfigArr = $Teams->read(new ContentParams());
        $this->linkName = $teamConfigArr['link_name'];
        $this->linkHref = $teamConfigArr['link_href'];
    }

    /**
     * Configure gettext domain
     */
    private function initi18n(): void
    {
        $locale = $this->getLang() . '.utf8';
        $domain = 'messages';
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, dirname(__DIR__, 2) . '/src/langs');
        textdomain($domain);
    }
}
