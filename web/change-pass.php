<?php declare(strict_types=1);
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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form to reset the password
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Reset password');

$template = 'change-pass.html';
$Response = new Response();
$Response->prepare($Request);

try {
    // check URL parameters
    if (!$Request->query->has('key') ||
        !$Request->query->has('deadline') ||
        Tools::checkId((int) $Request->query->get('userid')) === false) {
        throw new IllegalActionException('Bad parameters in url.');
    }

    // check deadline (fix #297)
    $deadline = Crypto::decrypt($Request->query->get('deadline'), Key::loadFromAsciiSafeString(\SECRET_KEY));

    if ($deadline < time()) {
        throw new ImproperActionException(_('Invalid link. Reset links are only valid for one hour.'));
    }

    $renderArr = array(
        'key' => $Request->query->filter('key', null, FILTER_SANITIZE_STRING),
        'deadline' => $Request->query->filter('deadline', null, FILTER_SANITIZE_STRING),
        'userid' => $Request->query->filter('userid', null, FILTER_SANITIZE_STRING),
    );
} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
