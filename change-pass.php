<?php
/**
 * change-pass.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form to reset the password
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Reset password');
    require_once 'app/head.inc.php';

    $Auth = new Auth();

    // check URL parameters
    if (!$Request->query->has('key') ||
        !$Request->query->has('deadline') ||
        Tools::checkId($Request->query->get('userid')) === false) {

        throw new Exception('Bad parameters in url.');
    }

    // check deadline (fix #297)
    $deadline = Crypto::decrypt($Request->query->get('deadline'), Key::loadFromAsciiSafeString(SECRET_KEY));

    if ($deadline < time()) {
        throw new Exception(_('Invalid link. Reset links are only valid for one hour.'));
    }

    $Response = new Response();
    $html = $Twig->render('change-pass.html', array(
        'Auth' => $Auth,
        'key' => $Request->query->filter('key', null, FILTER_SANITIZE_STRING),
        'deadline' => $Request->query->filter('deadline', null, FILTER_SANITIZE_STRING),
        'userid' => $Request->query->filter('userid', null, FILTER_SANITIZE_STRING)
    ));
    $Response->setContent($html);
    $Response->prepare($Request);
    $Response->send();

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
