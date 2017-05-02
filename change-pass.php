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
    if (!isset($_GET['key']) ||
        !isset($_GET['userid']) ||
        !isset($_GET['deadline']) ||
        Tools::checkId($_GET['userid']) === false) {

        throw new Exception('Bad parameters in url.');
    }

    // check deadline (fix #297)
    $deadline = Crypto::decrypt($_GET['deadline'], Key::loadFromAsciiSafeString(SECRET_KEY));

    if ($deadline < time()) {
        throw new Exception(_('Invalid link. Reset links are only valid for one hour.'));
    }
    echo $twig->render('change-pass.html', array(
        'Auth' => $Auth,
        'key' => filter_var($_GET['key'], FILTER_SANITIZE_STRING),
        'deadline' => filter_var($_GET['deadline'], FILTER_SANITIZE_STRING),
        'userid' => filter_var($_GET['userid'], FILTER_SANITIZE_STRING)

    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
