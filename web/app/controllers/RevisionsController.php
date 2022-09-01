<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\Revisions;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Revisions controller
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../experiments.php');

try {
    $Entity = (new EntityFactory($App->Users, (string) $Request->query->get('type')))->getEntity();
    $entityId = $Request->query->getInt('item_id');
    $Entity->setId($entityId);
    $Entity->canOrExplode('write');
    $Revisions = new Revisions(
        $Entity,
        (int) $App->Config->configArr['max_revisions'],
        (int) $App->Config->configArr['min_delta_revisions'],
        (int) $App->Config->configArr['min_days_revisions'],
    );

    if ($Request->query->get('action') === 'restore') {
        $revId = Check::id($Request->query->getInt('rev_id'));
        if ($revId === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }

        $Revisions->restore($revId);
        $App->Session->getFlashBag()->add('ok', _('Saved'));
    }

    if ($Entity instanceof Templates) {
        $Response = new RedirectResponse(sprintf('../../ucp.php?tab=3&templateid=%d', $entityId));
    } else {
        $Response = new RedirectResponse(sprintf('../../%s.php?mode=view&id=%d', $Entity->page, $entityId));
    }
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
