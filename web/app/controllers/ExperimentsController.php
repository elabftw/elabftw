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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Experiments
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();

try {

    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

    $Entity = new Experiments($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId((int) $Request->request->get('id'));
    }

    // CREATE EXPERIMENT
    // the only get request with redirect, rest is post called from js with json output
    if ($Request->query->has('create')) {
        if ($Request->query->has('tpl')) {
            $id = $Entity->create((int) $Request->query->get('tpl'));
        } else {
            $id = $Entity->create();
        }
        $Response = new RedirectResponse("../../experiments.php?mode=edit&id=" . $id);
    }

    // CREATE STEP
    if ($Request->request->has('createStep')) {
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
        $Entity->canOrExplode('write');

        if ($Entity->Steps->finish((int) $Request->request->get('stepId'))) {
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
        $Entity->canOrExplode('write');

        if ($Entity->Steps->destroy((int) $Request->request->get('stepId'))) {
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
        $Entity->canOrExplode('write');

        if ($Entity->Links->create((int) $Request->request->get('linkId'))) {
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
        $Entity->canOrExplode('write');

        if ($Entity->Links->destroy((int) $Request->request->get('linkId'))) {
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
        $Response->setData($Entity->getLinkList($Request->query->get('term')));
    }

    // TIMESTAMP
    if ($Request->request->has('timestamp')) {
        try {
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
            $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
            $Response->setData(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
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
    if ($Request->request->has('asn1') && \is_readable(\dirname(__DIR__, 3) . "/uploads/" . $Request->request->get('asn1'))) {
        $TrustedTimestamps = new TrustedTimestamps(new Config(), new Teams($App->Users), $Entity);

        $Response->setData(array(
            'res' => true,
            'msg' => $TrustedTimestamps->decodeAsn1($Request->request->get('asn1'))
        ));
    }


} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', $e->getMessage());
    $Response = new RedirectResponse("../../experiments.php");
}

$Response->send();
