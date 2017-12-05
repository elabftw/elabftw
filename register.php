<?php
/**
 * register.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Create an account
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Register');

try {
    // Check if we're logged in
    if ($Session->has('auth')) {
        throw new Exception(sprintf(
            _('Please %slogout%s before you register another account.'),
            "<a style='alert-link' href='app/logout.php'>",
            "</a>"
        ));
    }

    // local register might be disabled
    if ($App->Config->configArr['local_register'] === '0') {
        throw new Exception(_('No local account creation is allowed!'));
    }

    $Teams = new Teams($App->Users);
    $teamsArr = $Teams->readAll();

    $template = 'register.html';
    $renderArr = array('teamsArr' => $teamsArr);

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
