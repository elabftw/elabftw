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
use Symfony\Component\HttpFoundation\JsonResponse;

require_once dirname(__DIR__) . '/init.inc.php';

// this is here so privacy policy can be called from ajax without being auth
$Response = new JsonResponse();
$Response->setData(array(
    'privacy' => $App->Config->configArr['privacy_policy'],
    'tos' => $App->Config->configArr['terms_of_service'],
    'a11y' => $App->Config->configArr['a11y_statement'],
));
$Response->send();
