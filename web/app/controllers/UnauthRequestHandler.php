<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\PrivacyPolicy;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once dirname(__DIR__) . '/init.inc.php';

// this is here so privacy policy can be called from ajax without being auth
// at the moment it is the only request that comes in here so there is no need to actually process the payload
// because we're sure it's to read the privacy policy
$Response = new JsonResponse();
try {
    $PrivacyPolicy = new PrivacyPolicy($App->Config);
    $Response->setData(array(
        'res' => true,
        'msg' => _('Saved'),
        'value' => $PrivacyPolicy->read(new ContentParams()),
    ));
} catch (ResourceNotFoundException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} finally {
    $Response->send();
}
