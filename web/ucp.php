<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function chunk_split;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Services\MpdfQrProvider;
use Exception;
use RobThree\Auth\TwoFactorAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * User Control Panel
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('User Control Panel');

$Response = new Response();
$Response->prepare($Request);

try {
    $ApiKeys = new ApiKeys($App->Users);
    $apiKeysArr = $ApiKeys->readAll();

    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->readAll();

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->readInclusive();

    // TEAM GROUPS
    // Added Visibility clause
    $TeamGroups = new TeamGroups($App->Users);
    $visibilityArr = $TeamGroups->getVisibilityList();

    $template = 'ucp.html';
    $renderArr = array(
        'Entity' => $Templates,
        'apiKeysArr' => $apiKeysArr,
        'langsArr' => Tools::getLangsArr(),
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $templatesArr,
        'visibilityArr' => $visibilityArr,
    );

    // If we enable 2FA we need to provide the secret.
    // For user convenience we provide it as QR code and as plain text
    if ($App->Session->has('mfa_secret')) {
        $qrProvider = new MpdfQrProvider();
        $tfa = new TwoFactorAuth('eLabFTW', 6, 30, 'sha1', $qrProvider);

        $renderArr['mfaQRCodeDataUri'] = $tfa->getQRCodeImageAsDataUri($App->Users->userData['email'], $App->Session->get('mfa_secret'));
        $renderArr['mfaSecret'] = chunk_split($App->Session->get('mfa_secret'), 4, ' ');
    }
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
}
$Response->setContent($App->render($template, $renderArr));
$Response->send();
