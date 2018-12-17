<?php
/**
 * app/controllers/UsersController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Users infos from admin or sysadmin page with ajax request and json response
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$FormKey = new FormKey($Session);
$Response = new JsonResponse();
// default response is general error
$Response->setData(array(
    'res' => false,
    'msg' => Tools::error()
));

try {
    if (!$FormKey->validate($Request->request->get('fkvalue'), $Request->request->get('fkname'))) {
        throw new IllegalActionException('CSRF token failure.');
    }
    // (RE)GENERATE AN API KEY (from profile)
    if ($Request->request->has('generateApiKey')) {

        if ($App->Users->generateApiKey()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }

    // VALIDATE USER
    if ($Request->request->has('usersValidate')) {

        // you need to be at least admin to validate a user
        if (!$Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to validate user.');
        }

        // we need Config to send email. TODO make better constructors so we don't have to worry about that
        $targetUser = new Users((int) $Request->request->get('userid'), null, $App->Config);

        // check we validate user of our team
        if (($App->Users->userData['team'] !== $targetUser->userData['team']) && !$Session->get('is_sysadmin')) {
            throw new IllegalActionException('User tried to validate user from other team.');
        }

        // all good, validate user
        if ($targetUser->validate()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }

    // ARCHIVE USER
    if ($Request->request->has('usersArchive')) {

        // you need to be at least admin to archive a user
        if (!$Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to archive another user.');
        }
        $targetUser = new Users((int) $Request->request->get('userid'));

        if ($targetUser->archive()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }


    // DESTROY
    if ($Request->request->has('usersDestroy')) {

        // you need to be at least admin to delete a user
        if (!$Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to delete another user.');
        }

        // load data on the user to delete
        $targetUser = new Users((int) $Request->request->get('userid'));
        if (empty($targetUser)) {
            throw new IllegalActionException('User tried to delete inexisting user.');
        }

        // need to be sysadmin to delete user from other team
        if (($App->Users->userData['team'] !== $targetUser->userData['team']) && !$Session->get('is_sysadmin')) {
            throw new IllegalActionException('Admin user tried to delete user from other team.');
        }


        if ($targetUser->destroy()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Everything was purged successfully.')
            ));
        }
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true)
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));

} finally {
    $Response->send();
}
