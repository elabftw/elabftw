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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form to reset the password
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Reset password');

try {
    // check URL parameters
    if (!$Request->query->has('key') ||
        !$Request->query->has('deadline') ||
        Tools::checkId($Request->query->get('userid')) === false) {

        throw new Exception('Bad parameters in url.');
    }

    // check deadline (fix #297)
    $deadline = Crypto::decrypt($Request->query->get('deadline'), Key::loadFromAsciiSafeString(\SECRET_KEY));

    if ($deadline < time()) {
        throw new Exception(_('Invalid link. Reset links are only valid for one hour.'));
    }

    $template = 'change-pass.html';
    $renderArr = array(
        'key' => $Request->query->filter('key', null, FILTER_SANITIZE_STRING),
        'deadline' => $Request->query->filter('deadline', null, FILTER_SANITIZE_STRING),
        'userid' => $Request->query->filter('userid', null, FILTER_SANITIZE_STRING)
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
