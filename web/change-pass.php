<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\ResetPasswordKey;
use Exception;
use const SECRET_KEY;
use Symfony\Component\HttpFoundation\Response;
use function time;

/**
 * Form to reset the password
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Reset password');

$Response = new Response();
$Response->prepare($Request);

$template = 'error.html';
$renderArr = array();

try {
    // make sure this page is accessed with a key
    if (!$Request->query->has('key')) {
        throw new IllegalActionException('Bad parameters in url.');
    }

    // validate the key to show error if the key is expired
    $ResetPasswordKey = new ResetPasswordKey(time(), SECRET_KEY);
    $ResetPasswordKey->validate($Request->query->get('key'));

    $template = 'change-pass.html';
    $renderArr = array(
        'key' => $Request->query->filter('key', null, FILTER_SANITIZE_STRING),
    );
} catch (Exception $e) {
    $renderArr['error'] = $e->getMessage();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
