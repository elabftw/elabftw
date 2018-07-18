<?php
/**
 * app/controllers/ExperimentsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Experiments
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

    $Entity = new Experiments($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId($Request->request->get('id'));
    }

    // CREATE
    if ($Request->query->has('create')) {
        if ($Request->query->has('tpl')) {
            $id = $Entity->create($Request->query->get('tpl'));
        } else {
            $id = $Entity->create();
        }
        $Response = new RedirectResponse("../../experiments.php?mode=edit&id=" . $id);
    }

    // CREATE STEP
    if ($Request->request->has('createStep')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->Steps->create($Request->request->get('body'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // FINISH STEP
    if ($Request->request->has('finishStep')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->Steps->finish($Request->request->get('stepId'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // DESTROY STEP
    if ($Request->request->has('destroyStep')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->Steps->destroy($Request->request->get('stepId'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Step deleted successfully')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // CREATE LINK
    if ($Request->request->has('createLink')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->Links->create($Request->request->get('linkId'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // DESTROY LINK
    if ($Request->request->has('destroyLink')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->Links->destroy($Request->request->get('linkId'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Link deleted successfully')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // GET LINK LIST
    if ($Request->query->has('term')) {
        $Response = new JsonResponse();
        $Response->setData($Entity->getLinkList($Request->query->get('term')));
    }

    // TIMESTAMP
    if ($Request->request->has('timestamp')) {
        try {
            $Response = new JsonResponse();
            $Entity->canOrExplode('write');
            if ($Entity->isTimestampable()) {
                $TrustedTimestamps = new TrustedTimestamps(new Config(), new Teams($App->Users), $Entity);
                if ($TrustedTimestamps->timeStamp()) {
                    $Response->setData(array(
                        'res' => true
                    ));
                }
            } else {
                $Response->setData(array(
                    'res' => false,
                    'msg' => _('This experiment cannot be timestamped!')
                ));
            }
        } catch (Exception $e) {
            $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
            $Response->setData(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        $Teams = new Teams($App->Users);
        $teamConfigArr = $Teams->read();

        if (($teamConfigArr['deletable_xp'] == '0') && !$Session->get('is_admin')) {
            $Response->setData(array(
                'res' => false,
                'msg' => _("You don't have the rights to delete this experiment.")
            ));
        } else {
            if ($Entity->destroy()) {
                $Response->setData(array(
                    'res' => true,
                    'msg' => _('Experiment successfully deleted')
                ));
            } else {
                $Response->setData(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        }
    }

    // DECODE ASN1 TOKEN
    if ($Request->request->has('asn1') && is_readable(ELAB_ROOT . "uploads/" . $Request->request->get('asn1'))) {
        $Response = new JsonResponse();
        $TrustedTimestamps = new TrustedTimestamps(new Config(), new Teams($App->Users), $Entity);

        $Response->setData(array(
            'res' => true,
            'msg' => $TrustedTimestamps->decodeAsn1($_POST['asn1'])
        ));
    }

    $Response->send();

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    $Session->getFlashBag()->add('ko', Tools::error());
    header('Location: ../../experiments.php');
}
